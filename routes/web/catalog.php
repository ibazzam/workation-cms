<?php

use App\Support\VendorPropertyCompatibilityReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$resolveReviewStats = static function (array $lookupIds, ?string $categoryKey = null, ?int $vendorUserId = null): \Illuminate\Support\Collection {
    $normalizedIds = collect($lookupIds)
        ->map(static fn ($id) => (int) $id)
        ->filter(static fn (int $id): bool => $id > 0)
        ->unique()
        ->values();

    if ($normalizedIds->isEmpty()) {
        return collect();
    }

    $normalizedCategory = strtolower(trim((string) $categoryKey));
    $categoryAliases = collect([
        $normalizedCategory,
        str_replace('-', '_', $normalizedCategory),
        str_replace('_', '-', $normalizedCategory),
    ])
        ->filter(static fn ($value): bool => $value !== '')
        ->unique()
        ->values();

    $aggregatedByProperty = [];
    $reviewTableCandidates = ['vendor_property_reviews', 'property_reviews', 'customer_reviews', 'vendor_reviews'];

    foreach ($reviewTableCandidates as $reviewTable) {
        if (!Schema::hasTable($reviewTable)) {
            continue;
        }

        $columns = Schema::getColumnListing($reviewTable);
        $propertyKey = collect(['vendor_property_id', 'property_id', 'listing_id', 'entity_id'])
            ->first(static fn ($column) => in_array($column, $columns, true));
        $ratingKey = collect(['rating', 'rating_value', 'review_score', 'score'])
            ->first(static fn ($column) => in_array($column, $columns, true));

        if ($propertyKey === null || $ratingKey === null) {
            continue;
        }

        $reviewQuery = DB::table($reviewTable)
            ->whereIn($propertyKey, $normalizedIds->all())
            ->where($ratingKey, '>', 0);

        $statusKey = collect(['status', 'review_status'])
            ->first(static fn ($column) => in_array($column, $columns, true));
        if ($statusKey !== null) {
            $reviewQuery->whereIn(DB::raw('LOWER(' . $statusKey . ')'), ['approved', 'published', 'active']);
        }

        if ($categoryAliases->isNotEmpty()) {
            $categoryColumn = collect(['listing_category', 'entity_type', 'category', 'service_type', 'module'])
                ->first(static fn ($column) => in_array($column, $columns, true));

            if ($categoryColumn !== null) {
                $reviewQuery->whereIn(DB::raw('LOWER(' . $categoryColumn . ')'), $categoryAliases->all());
            }
        }

        if ($vendorUserId !== null && $vendorUserId > 0 && in_array('vendor_user_id', $columns, true)) {
            $reviewQuery->where('vendor_user_id', $vendorUserId);
        }

        $rows = $reviewQuery
            ->select([
                DB::raw($propertyKey . ' as property_lookup_id'),
                DB::raw('AVG(' . $ratingKey . ') as avg_rating'),
                DB::raw('COUNT(*) as review_count'),
            ])
            ->groupBy($propertyKey)
            ->get();

        foreach ($rows as $row) {
            $propertyId = (int) ($row->property_lookup_id ?? 0);
            $reviewCount = max(0, (int) ($row->review_count ?? 0));
            $avgRating = max(0.0, (float) ($row->avg_rating ?? 0));

            if ($propertyId <= 0 || $reviewCount <= 0 || $avgRating <= 0) {
                continue;
            }

            if (!array_key_exists($propertyId, $aggregatedByProperty)) {
                $aggregatedByProperty[$propertyId] = ['weighted_sum' => 0.0, 'count' => 0];
            }

            $aggregatedByProperty[$propertyId]['weighted_sum'] += ($avgRating * $reviewCount);
            $aggregatedByProperty[$propertyId]['count'] += $reviewCount;
        }
    }

    return collect($aggregatedByProperty)->map(static function (array $stats): array {
        $count = max(0, (int) ($stats['count'] ?? 0));
        $weightedSum = max(0.0, (float) ($stats['weighted_sum'] ?? 0));

        return [
            'rating' => $count > 0 ? round($weightedSum / $count, 2) : 0.0,
            'reviews_count' => $count,
        ];
    });
};

Route::get('/catalog/{category}', function (Request $request, string $category) use ($resolveReviewStats) {
    $categoryMap = [
        'accommodation' => ['label' => 'Accommodation', 'subtitle' => 'Hotels, resorts, villas, and guesthouses.', 'hero_image_url' => ''],
        'land-transport' => ['label' => 'Land Transport', 'subtitle' => 'Cars, vans, and local ground transfers.', 'hero_image_url' => ''],
        'excursion' => ['label' => 'Excursion', 'subtitle' => 'Experiences, tours, and activity packages.', 'hero_image_url' => ''],
        'water_sports' => ['label' => 'Water Sports', 'subtitle' => 'Diving, snorkeling, and sea activity experiences.', 'hero_image_url' => ''],
        'remote_workspace' => ['label' => 'Remote Workspace', 'subtitle' => 'Work-friendly spaces and productivity stays.', 'hero_image_url' => ''],
        'conference_room' => ['label' => 'Conference & Meeting Spaces', 'subtitle' => 'Hotel conference rooms, halls, and meeting spaces for events, training, seminars.', 'hero_image_url' => ''],
        'resort_day_visit' => ['label' => 'Resort Day Visit', 'subtitle' => 'Day access offers for top resort properties.', 'hero_image_url' => ''],
        'restaurant' => ['label' => 'Restaurant', 'subtitle' => 'Island-specific dining - find restaurants on your island.', 'hero_image_url' => ''],
        'vehicle_rental' => ['label' => 'Vehicle Rental', 'subtitle' => 'Cars, bikes, speedboats, and private vessel hire by island.', 'hero_image_url' => ''],
        'sea_transport' => ['label' => 'Sea Transport & Ferries', 'subtitle' => 'Public ferries, scheduled sea routes, and inter-island sea travel.', 'hero_image_url' => ''],
        'liveaboard' => ['label' => 'Liveaboard / Safari', 'subtitle' => 'Multi-day safari vessel journeys with onboard stay.', 'hero_image_url' => ''],
    ];

    $requestedCategoryKey = strtolower(trim($category));
    $categoryAliases = [
        // Legacy marine-transport URLs redirect transparently to sea_transport.
        'marine-transport' => 'sea_transport',
        'marine_transport' => 'sea_transport',
        'land_transport' => 'land-transport',
        'remote-workspace' => 'remote_workspace',
        'conference-room' => 'conference_room',
        'resort-day-visit' => 'resort_day_visit',
        'vehicle-rental' => 'vehicle_rental',
        'water-sports' => 'water_sports',
        'sea-transport' => 'sea_transport',
    ];

    $categoryKey = $categoryAliases[$requestedCategoryKey] ?? $requestedCategoryKey;
    if (!array_key_exists($categoryKey, $categoryMap)) {
        abort(404);
    }

    // Map URL slug (hyphens) to DB listing_category value (underscores)
    $dbCategoryKey = str_replace('-', '_', $categoryKey);

    if (portalHeroStoredValueForSlot($categoryKey) !== '') {
        // Always use the slot proxy URL so category hero updates/removals are
        // reflected immediately without stale direct-object cache artifacts.
        $categoryMap[$categoryKey]['hero_image_url'] = '/media/portal/hero/' . $categoryKey;
    }

    $resolvedCategoryHeroImage = trim((string) ($categoryMap[$categoryKey]['hero_image_url'] ?? ''));
    $resolvedCategoryHeroImage = portalManagedMediaUrlFromPath($resolvedCategoryHeroImage) ?? $resolvedCategoryHeroImage;
    $categoryMap[$categoryKey]['hero_image_url'] = $resolvedCategoryHeroImage;

    $queryText = trim((string) $request->query('q', ''));
    $atollFilter = trim((string) $request->query('atoll', ''));
    $islandFilter = trim((string) $request->query('island', ''));
    $currentIsland = trim((string) $request->query('current_island', ''));
    $pickupIsland = trim((string) $request->query('pickup_island', ''));
    $reservationDatetime = trim((string) $request->query('reservation_datetime', ''));
    $partySize = max(1, (int) $request->query('party_size', 2));
    $vehicleKind = trim((string) $request->query('vehicle_kind', ''));
    $landTransmission = trim((string) $request->query('transmission', ''));
    $landSupplier = trim((string) $request->query('supplier', ''));
    $pickupDatetime = trim((string) $request->query('pickup_datetime', ''));
    $dropoffDatetime = trim((string) $request->query('dropoff_datetime', ''));
    $activityType = trim((string) $request->query('activity_type', ''));
    $difficulty = trim((string) $request->query('difficulty', ''));
    $excursionDate = trim((string) $request->query('excursion_date', ''));
    $workspaceTypeFilter = trim((string) $request->query('workspace_type_filter', ''));
    $internetSpeed = trim((string) $request->query('internet_speed', ''));
    $workspaceStart = trim((string) $request->query('workspace_start', ''));
    $workspaceEnd = trim((string) $request->query('workspace_end', ''));
    $timeSlot = trim((string) $request->query('time_slot', ''));
    $facilityType = trim((string) $request->query('facility_type', ''));
    $visitDate = trim((string) $request->query('visit_date', ''));
    $conferenceEventType = trim((string) $request->query('conference_event_type', ''));
    $conferenceCapacity = (int) $request->query('conference_capacity', 0);
    $conferenceDate = trim((string) $request->query('conference_date', ''));
    $minPrice = (float) $request->query('min_price', 0);
    $maxPrice = (float) $request->query('max_price', 0);
    $minRating = (float) $request->query('min_rating', 0);
    $minReviews = max(0, (int) $request->query('min_reviews', 0));
    $distanceKm = (float) $request->query('distance_km', 0);
    $userLat = (float) $request->query('user_lat', 0);
    $userLng = (float) $request->query('user_lng', 0);
    $amenitiesQuery = trim((string) $request->query('amenities', ''));
    $amenityKeywords = collect(preg_split('/[,\n]+/', $amenitiesQuery) ?: [])
        ->map(static fn ($value) => trim((string) $value))
        ->filter(static fn ($value) => $value !== '')
        ->unique()
        ->values();
    $availabilityOnlyRaw = strtolower(trim((string) $request->query('availability_only', '')));
    $availabilityOnly = in_array($availabilityOnlyRaw, ['1', 'true', 'yes', 'on'], true);
    $sort = strtolower(trim((string) $request->query('sort', 'recommended')));
    $originPointFilter = trim((string) $request->query('origin_point', ''));
    $destinationPointFilter = trim((string) $request->query('destination_point', ''));
    $visitorResidency = function_exists('workationDetectVisitorResidency')
        ? workationDetectVisitorResidency($request)
        : (strtoupper(trim((string) ($request->header('CF-IPCountry') ?? $request->header('X-Country-Code') ?? $request->header('X-GeoIP-Country') ?? ''))) === 'MV'
            ? 'local_resident'
            : 'foreign_national');
    $travelDate = trim((string) $request->query('travel_date', ''));
    $tripTypeFilter = trim((string) $request->query('trip_type', 'one_way'));
    $guestTypeFilter = trim((string) $request->query('guest_type', $visitorResidency === 'local_resident' ? 'local_resident' : 'foreign_national'));
    $seatsRequested = max(1, (int) $request->query('seats', 1));
    $liveaboardStartPoint = trim((string) $request->query('start_point', ''));
    $liveaboardEndPoint = trim((string) $request->query('end_point', ''));
    $liveaboardDate = trim((string) $request->query('journey_date', ''));
    $mvrUsdRate = max(0.0, (float) env('MVR_USD_RATE', 15.42));

    // For island-specific categories (restaurant, vehicle_rental), fall back to
    // current_island or pickup_island when the generic island filter is not set.
    $effectiveIslandFilter = $islandFilter;
    if ($effectiveIslandFilter === '' && in_array($categoryKey, ['restaurant', 'vehicle_rental'], true)) {
        if ($currentIsland !== '') {
            $effectiveIslandFilter = $currentIsland;
        } elseif ($pickupIsland !== '') {
            $effectiveIslandFilter = $pickupIsland;
        }
    }

    $catalogProperties = collect();
    $catalogPropertyMediaByProperty = collect();
    $atollOptions = collect();
    $islandOptions = collect();
    $transportDestinationOptions = collect();

    $categoryTable = \App\Support\VendorPropertyCompatibilityReader::categoryTableNameFor($dbCategoryKey);
    {
        $propertiesQuery = VendorPropertyCompatibilityReader::categoryApprovedBaseQuery($dbCategoryKey);

        $searchColumns = [];
        foreach (['name', 'listing_name', 'atoll', 'island', 'city', 'description', 'listing_details', 'amenities', 'facilities', 'pickup_location', 'dropoff_location', 'origin_point', 'destination_point'] as $candidateColumn) {
            if (Schema::hasColumn($categoryTable, $candidateColumn)) {
                $searchColumns[] = $candidateColumn;
            }
        }

        if ($queryText !== '' && !empty($searchColumns)) {
            $propertiesQuery->where(function ($query) use ($searchColumns, $queryText) {
                foreach ($searchColumns as $index => $column) {
                    if ($index === 0) {
                        $query->where($column, 'like', '%' . $queryText . '%');
                    } else {
                        $query->orWhere($column, 'like', '%' . $queryText . '%');
                    }
                }
            });
        }

        if ($atollFilter !== '' && Schema::hasColumn($categoryTable, 'atoll')) {
            $propertiesQuery->whereRaw('LOWER(atoll) = ?', [strtolower($atollFilter)]);
        }

        if ($effectiveIslandFilter !== '' && Schema::hasColumn($categoryTable, 'island')) {
            $propertiesQuery->whereRaw('LOWER(island) = ?', [strtolower($effectiveIslandFilter)]);
        }

        $originSearchColumns = [];
        foreach (['pickup_location', 'origin_point', 'island', 'city', 'atoll'] as $candidateColumn) {
            if (Schema::hasColumn($categoryTable, $candidateColumn)) {
                $originSearchColumns[] = $candidateColumn;
            }
        }

        if ($originPointFilter !== '' && !empty($originSearchColumns)) {
            $propertiesQuery->where(function ($query) use ($originSearchColumns, $originPointFilter) {
                foreach ($originSearchColumns as $index => $column) {
                    if ($index === 0) {
                        $query->where($column, 'like', '%' . $originPointFilter . '%');
                    } else {
                        $query->orWhere($column, 'like', '%' . $originPointFilter . '%');
                    }
                }
            });
        }

        $destinationSearchColumns = [];
        foreach (['dropoff_location', 'destination_point', 'island', 'city', 'atoll'] as $candidateColumn) {
            if (Schema::hasColumn($categoryTable, $candidateColumn)) {
                $destinationSearchColumns[] = $candidateColumn;
            }
        }

        if ($destinationPointFilter !== '' && !empty($destinationSearchColumns)) {
            $propertiesQuery->where(function ($query) use ($destinationSearchColumns, $destinationPointFilter) {
                foreach ($destinationSearchColumns as $index => $column) {
                    if ($index === 0) {
                        $query->where($column, 'like', '%' . $destinationPointFilter . '%');
                    } else {
                        $query->orWhere($column, 'like', '%' . $destinationPointFilter . '%');
                    }
                }
            });
        }

        if ($dbCategoryKey === 'land_transport') {
            $vehicleTypeFilter = trim((string) $request->query('vehicle_type', ''));

            if ($vehicleTypeFilter !== '') {
                $vehicleColumns = [];
                foreach (['vehicle_type', 'transport_type', 'service_type', 'name', 'listing_name', 'listing_details'] as $candidateColumn) {
                    if (Schema::hasColumn($categoryTable, $candidateColumn)) {
                        $vehicleColumns[] = $candidateColumn;
                    }
                }

                if (!empty($vehicleColumns)) {
                    $needle = strtolower($vehicleTypeFilter);
                    $propertiesQuery->where(function ($query) use ($vehicleColumns, $needle) {
                        foreach ($vehicleColumns as $index => $column) {
                            $pattern = '%' . $needle . '%';
                            if ($index === 0) {
                                $query->whereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", [$pattern]);
                            } else {
                                $query->orWhereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", [$pattern]);
                            }
                        }
                    });
                }
            }

            if ($landTransmission !== '') {
                $transmissionColumns = [];
                foreach (['transmission', 'gearbox_type', 'listing_details', 'description'] as $candidateColumn) {
                    if (Schema::hasColumn($categoryTable, $candidateColumn)) {
                        $transmissionColumns[] = $candidateColumn;
                    }
                }

                if (!empty($transmissionColumns)) {
                    $needle = strtolower($landTransmission);
                    $propertiesQuery->where(function ($query) use ($transmissionColumns, $needle) {
                        foreach ($transmissionColumns as $index => $column) {
                            $pattern = '%' . $needle . '%';
                            if ($index === 0) {
                                $query->whereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", [$pattern]);
                            } else {
                                $query->orWhereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", [$pattern]);
                            }
                        }
                    });
                }
            }

            if ($landSupplier !== '' && Schema::hasColumn($categoryTable, 'vendor_user_id') && Schema::hasTable('users')) {
                $supplierIds = DB::table('users')
                    ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($landSupplier) . '%'])
                    ->pluck('id')
                    ->map(static fn ($id) => (int) $id)
                    ->filter(static fn (int $id) => $id > 0)
                    ->values();

                if ($supplierIds->isNotEmpty()) {
                    $propertiesQuery->whereIn('vendor_user_id', $supplierIds->all());
                } else {
                    $propertiesQuery->whereRaw('1 = 0');
                }
            }
        }

        // Price filters are applied after derived-price normalization so listings
        // that store pricing in details JSON are handled correctly.

        $popularityColumns = ['bookings_count', 'total_bookings', 'wishlist_count', 'view_count'];
        $bookedColumns = ['bookings_count', 'total_bookings'];
        $reviewColumns = ['review_score', 'rating_average', 'average_rating', 'rating'];

        $firstExistingColumn = static function (array $columns) use ($categoryTable): ?string {
            foreach ($columns as $column) {
                if (Schema::hasColumn($categoryTable, $column)) {
                    return $column;
                }
            }

            return null;
        };

        $popularityColumn = $firstExistingColumn($popularityColumns);
        $bookedColumn = $firstExistingColumn($bookedColumns);
        $reviewColumn = $firstExistingColumn($reviewColumns);
        $reviewCountColumn = $firstExistingColumn(['reviews_count', 'review_count', 'rating_count', 'total_reviews']);

        if ($minRating > 0 && $reviewColumn !== null) {
            $propertiesQuery->where($reviewColumn, '>=', $minRating);
        }

        if ($minReviews > 0 && $reviewCountColumn !== null) {
            $propertiesQuery->where($reviewCountColumn, '>=', $minReviews);
        }

        if ($amenityKeywords->isNotEmpty()) {
            $amenitySearchColumns = [];
            foreach (['amenities', 'facilities', 'listing_details', 'description', 'name', 'listing_name'] as $candidateColumn) {
                if (Schema::hasColumn($categoryTable, $candidateColumn)) {
                    $amenitySearchColumns[] = $candidateColumn;
                }
            }

            if (!empty($amenitySearchColumns)) {
                $propertiesQuery->where(function ($query) use ($amenitySearchColumns, $amenityKeywords) {
                    foreach ($amenityKeywords as $keyword) {
                        $query->where(function ($keywordQuery) use ($amenitySearchColumns, $keyword) {
                            $normalizedKeyword = strtolower((string) $keyword);
                            foreach ($amenitySearchColumns as $index => $column) {
                                $pattern = '%' . $normalizedKeyword . '%';
                                if ($index === 0) {
                                    $keywordQuery->whereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", [$pattern]);
                                } else {
                                    $keywordQuery->orWhereRaw("LOWER(COALESCE({$column}, '')) LIKE ?", [$pattern]);
                                }
                            }
                        });
                    }
                });
            }
        }

        if ($availabilityOnly) {
            $availabilityBooleanColumns = [];
            foreach (['is_available', 'available', 'is_bookable', 'is_active_listing'] as $candidateColumn) {
                if (Schema::hasColumn($categoryTable, $candidateColumn)) {
                    $availabilityBooleanColumns[] = $candidateColumn;
                }
            }

            $availabilityStatusColumn = Schema::hasColumn($categoryTable, 'availability_status') ? 'availability_status' : null;

            if (!empty($availabilityBooleanColumns) || $availabilityStatusColumn !== null) {
                $propertiesQuery->where(function ($query) use ($availabilityBooleanColumns, $availabilityStatusColumn) {
                    foreach ($availabilityBooleanColumns as $index => $column) {
                        if ($index === 0) {
                            $query->where($column, 1);
                        } else {
                            $query->orWhere($column, 1);
                        }
                    }

                    if ($availabilityStatusColumn !== null) {
                        $query->orWhereIn(DB::raw('LOWER(' . $availabilityStatusColumn . ')'), ['available', 'open', 'in_stock', 'ready']);
                    }
                });
            }
        }

        $latitudeColumn = $firstExistingColumn(['latitude', 'lat', 'location_lat', 'geo_lat', 'map_latitude']);
        $longitudeColumn = $firstExistingColumn(['longitude', 'lng', 'location_lng', 'geo_lng', 'map_longitude']);

        if (
            $distanceKm > 0
            && $userLat !== 0.0
            && $userLng !== 0.0
            && $latitudeColumn !== null
            && $longitudeColumn !== null
        ) {
            $distanceSql = '(6371 * acos(least(1, greatest(-1, cos(radians(?)) * cos(radians(' . $latitudeColumn . ')) * cos(radians(' . $longitudeColumn . ') - radians(?)) + sin(radians(?)) * sin(radians(' . $latitudeColumn . '))))))';
            $propertiesQuery->whereRaw($distanceSql . ' <= ?', [$userLat, $userLng, $userLat, $distanceKm]);
        }

        if ($sort === 'distance_nearest' && $latitudeColumn !== null && $longitudeColumn !== null && $userLat !== 0.0 && $userLng !== 0.0) {
            $distanceSql = '(6371 * acos(least(1, greatest(-1, cos(radians(?)) * cos(radians(' . $latitudeColumn . ')) * cos(radians(' . $longitudeColumn . ') - radians(?)) + sin(radians(?)) * sin(radians(' . $latitudeColumn . '))))))';
            $propertiesQuery->orderByRaw($distanceSql . ' asc', [$userLat, $userLng, $userLat]);
        } elseif ($sort === 'most_wanted' && $popularityColumn !== null) {
            $propertiesQuery->orderByDesc($popularityColumn);
        } elseif ($sort === 'most_booked' && $bookedColumn !== null) {
            $propertiesQuery->orderByDesc($bookedColumn);
        } elseif ($sort === 'highest_reviews' && $reviewColumn !== null) {
            $propertiesQuery->orderByDesc($reviewColumn);
        } else {
            $propertiesQuery->orderByDesc('updated_at');
        }

        $catalogProperties = $propertiesQuery->limit(80)->get()
            ->map(static function ($row) {
                // Normalize id to vendor_property_id so media lookups and detail-page URLs
                // work correctly for both old migrated rows and new self-referencing rows.
                $row->dedicated_row_id = isset($row->id) ? (int) $row->id : 0;
                $vendorPropertyId = (int) ($row->vendor_property_id ?? 0);
                $row->id = $vendorPropertyId > 0 ? $vendorPropertyId : (int) ($row->id ?? 0);
                return $row;
            });
        $propertyIds = $catalogProperties
            ->pluck('id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->values();
        $propertyLookupIds = $catalogProperties
            ->flatMap(static fn ($row) => workationPropertyLookupIds($row))
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values();

        // For accommodation, card price must come from room-level RO pricing only.
        // Source order: vendor_property_room_categories.meal_plan_room_only_price (or base_price)
        // then accommodation_rooms.base_price_per_night (or base_price).
        if ($dbCategoryKey === 'accommodation' && $propertyLookupIds->isNotEmpty()) {
            $combinedRoomPricesByProperty = collect();
            $mergeMinPriceMaps = static function ($baseMap, $incomingMap) {
                $resolved = collect($baseMap)
                    ->mapWithKeys(static fn ($price, $propertyId) => [(int) $propertyId => (float) $price])
                    ->filter(static fn ($price, $propertyId) => (int) $propertyId > 0 && is_numeric($price) && (float) $price > 0);

                foreach (collect($incomingMap) as $propertyId => $price) {
                    $normalizedPropertyId = (int) $propertyId;
                    $normalizedPrice = (float) $price;
                    if ($normalizedPropertyId <= 0 || $normalizedPrice <= 0) {
                        continue;
                    }

                    if (!$resolved->has($normalizedPropertyId) || $normalizedPrice < (float) $resolved->get($normalizedPropertyId)) {
                        $resolved->put($normalizedPropertyId, $normalizedPrice);
                    }
                }

                return $resolved;
            };

            $legacyRoomPropertyColumns = [];
            if (Schema::hasTable('vendor_property_room_categories')) {
                if (Schema::hasColumn('vendor_property_room_categories', 'vendor_property_id')) {
                    $legacyRoomPropertyColumns[] = 'vendor_property_id';
                }
                if (Schema::hasColumn('vendor_property_room_categories', 'property_id')) {
                    $legacyRoomPropertyColumns[] = 'property_id';
                }
            }

            if (!empty($legacyRoomPropertyColumns)
            ) {
                $legacyPriceColumns = [];
                foreach (['meal_plan_room_only_price', 'room_only_price', 'price_per_night', 'base_price'] as $candidateColumn) {
                    if (Schema::hasColumn('vendor_property_room_categories', $candidateColumn)) {
                        $legacyPriceColumns[] = $candidateColumn;
                    }
                }

                if (!empty($legacyPriceColumns)) {
                $legacyRoomPrices = DB::table('vendor_property_room_categories')
                    ->where(static function ($query) use ($legacyRoomPropertyColumns, $propertyLookupIds) {
                        foreach ($legacyRoomPropertyColumns as $index => $propertyColumn) {
                            if ($index === 0) {
                                $query->whereIn($propertyColumn, $propertyLookupIds->all());
                            } else {
                                $query->orWhereIn($propertyColumn, $propertyLookupIds->all());
                            }
                        }
                    })
                    ->get(array_merge($legacyRoomPropertyColumns, $legacyPriceColumns))
                    ->flatMap(static function ($row) use ($legacyRoomPropertyColumns, $legacyPriceColumns) {
                        $prices = collect($legacyPriceColumns)
                            ->map(static fn ($column) => (float) ($row->{$column} ?? 0))
                            ->filter(static fn (float $value) => $value > 0)
                            ->values();

                        if ($prices->isEmpty()) {
                            return [];
                        }

                        return collect($legacyRoomPropertyColumns)
                            ->map(static fn ($column) => (int) ($row->{$column} ?? 0))
                            ->filter(static fn (int $id): bool => $id > 0)
                            ->flatMap(static fn (int $propertyId) => $prices->map(static fn (float $price) => [
                                'property_id' => $propertyId,
                                'price' => $price,
                            ]));
                    })
                    ->groupBy(static fn (array $entry) => (int) ($entry['property_id'] ?? 0))
                    ->map(static function ($rows) {
                        return collect($rows)
                            ->map(static fn (array $entry) => (float) ($entry['price'] ?? 0))
                            ->filter(static fn (float $value): bool => $value > 0)
                            ->min();
                    })
                    ->filter(static fn ($value) => is_numeric($value) && (float) $value > 0);

                $combinedRoomPricesByProperty = $mergeMinPriceMaps($combinedRoomPricesByProperty, $legacyRoomPrices);
                }
            }

            if (Schema::hasTable('accommodation_rooms') && Schema::hasColumn('accommodation_rooms', 'property_id')) {
                $hasRoomActiveColumn = Schema::hasColumn('accommodation_rooms', 'is_active');
                $hasNightlyColumn = Schema::hasColumn('accommodation_rooms', 'base_price_per_night');
                $hasRoomOnlyColumn = Schema::hasColumn('accommodation_rooms', 'room_only_price');
                $hasPerNightColumn = Schema::hasColumn('accommodation_rooms', 'price_per_night');
                $hasLegacyRoomPriceColumn = Schema::hasColumn('accommodation_rooms', 'base_price');

                if ($hasNightlyColumn || $hasRoomOnlyColumn || $hasPerNightColumn || $hasLegacyRoomPriceColumn) {
                    $roomPriceColumns = ['property_id'];
                    if ($hasNightlyColumn) {
                        $roomPriceColumns[] = 'base_price_per_night';
                    }
                    if ($hasRoomOnlyColumn) {
                        $roomPriceColumns[] = 'room_only_price';
                    }
                    if ($hasPerNightColumn) {
                        $roomPriceColumns[] = 'price_per_night';
                    }
                    if ($hasLegacyRoomPriceColumn) {
                        $roomPriceColumns[] = 'base_price';
                    }

                    $roomPriceRows = DB::table('accommodation_rooms')
                        ->whereIn('property_id', $propertyLookupIds->all())
                        ->when($hasRoomActiveColumn, static function ($query) {
                            $query->where(static function ($activeQuery) {
                                $activeQuery->where('is_active', 1)
                                    ->orWhereNull('is_active');
                            });
                        })
                        ->get($roomPriceColumns);

                    $canonicalRoomPrices = $roomPriceRows
                        ->groupBy(static fn ($row) => (int) ($row->property_id ?? 0))
                        ->map(static function ($rows) {
                            return collect($rows)
                                ->map(static function ($row) {
                                    $nightly = isset($row->base_price_per_night) ? (float) $row->base_price_per_night : 0;
                                    $roomOnly = isset($row->room_only_price) ? (float) $row->room_only_price : 0;
                                    $perNight = isset($row->price_per_night) ? (float) $row->price_per_night : 0;
                                    $legacy = isset($row->base_price) ? (float) $row->base_price : 0;
                                    if ($nightly > 0) {
                                        return $nightly;
                                    }
                                    if ($roomOnly > 0) {
                                        return $roomOnly;
                                    }
                                    if ($perNight > 0) {
                                        return $perNight;
                                    }

                                    return $legacy;
                                })
                                ->filter(static fn (float $value) => $value > 0)
                                ->min();
                        })
                        ->filter(static fn ($value) => is_numeric($value) && (float) $value > 0);

                    $combinedRoomPricesByProperty = $mergeMinPriceMaps($combinedRoomPricesByProperty, $canonicalRoomPrices);
                }
            }

            // Force accommodation pricing to come only from room-level tables.
            // This prevents stale vendor_properties.base_price from showing on cards.
            $catalogProperties = $catalogProperties->map(static function ($prop) use ($combinedRoomPricesByProperty) {
                $prop->base_price = 0;
                $lookupId = collect(workationPropertyLookupIds($prop))
                    ->first(static fn (int $candidateId) => $combinedRoomPricesByProperty->has($candidateId));

                if (is_int($lookupId) && $lookupId > 0) {
                    $prop->base_price = (float) ($combinedRoomPricesByProperty->get($lookupId) ?? 0);
                }

                return $prop;
            });

            if ($minPrice > 0 || $maxPrice > 0) {
                $catalogProperties = $catalogProperties->filter(static function ($property) use ($minPrice, $maxPrice) {
                    $price = (float) ($property->base_price ?? 0);
                    if ($minPrice > 0 && $price < $minPrice) {
                        return false;
                    }
                    if ($maxPrice > 0 && $price > $maxPrice) {
                        return false;
                    }
                    return true;
                })->values();
            }

            if ($sort === 'price_low_high') {
                $catalogProperties = $catalogProperties->sortBy(static fn ($property) => (float) ($property->base_price ?? 0))->values();
            } elseif ($sort === 'price_high_low') {
                $catalogProperties = $catalogProperties->sortByDesc(static fn ($property) => (float) ($property->base_price ?? 0))->values();
            }

            $propertyIds = $catalogProperties
                ->pluck('id')
                ->map(static fn ($id) => (int) $id)
                ->filter(static fn (int $id) => $id > 0)
                ->values();
            $propertyLookupIds = $catalogProperties
                ->flatMap(static fn ($row) => workationPropertyLookupIds($row))
                ->filter(static fn (int $id) => $id > 0)
                ->unique()
                ->values();
        } else {
            $catalogProperties = $catalogProperties->map(static function ($property) {
                $derivedPrice = workationDerivedListingBasePrice($property);
                if ($derivedPrice > 0) {
                    $property->base_price = $derivedPrice;
                }

                return $property;
            })->values();

            if ($minPrice > 0 || $maxPrice > 0) {
                $catalogProperties = $catalogProperties->filter(static function ($property) use ($minPrice, $maxPrice) {
                    $price = (float) ($property->base_price ?? 0);
                    if ($minPrice > 0 && $price < $minPrice) {
                        return false;
                    }
                    if ($maxPrice > 0 && $price > $maxPrice) {
                        return false;
                    }
                    return true;
                })->values();
            }

            if ($sort === 'price_low_high') {
                $catalogProperties = $catalogProperties
                    ->sortBy(static fn ($property) => (float) ($property->base_price ?? 0))
                    ->values();
            } elseif ($sort === 'price_high_low') {
                $catalogProperties = $catalogProperties
                    ->sortByDesc(static fn ($property) => (float) ($property->base_price ?? 0))
                    ->values();
            }

            $propertyIds = $catalogProperties
                ->pluck('id')
                ->map(static fn ($id) => (int) $id)
                ->filter(static fn (int $id) => $id > 0)
                ->values();
            $propertyLookupIds = $catalogProperties
                ->flatMap(static fn ($row) => workationPropertyLookupIds($row))
                ->filter(static fn (int $id) => $id > 0)
                ->unique()
                ->values();
        }

        $reviewStatsByLookupId = $resolveReviewStats($propertyLookupIds->all(), $dbCategoryKey, null);
        if ($reviewStatsByLookupId->isNotEmpty()) {
            $catalogProperties = $catalogProperties->map(static function ($property) use ($reviewStatsByLookupId) {
                $matchedLookupId = collect(workationPropertyLookupIds($property))
                    ->first(static fn (int $candidateId): bool => $reviewStatsByLookupId->has($candidateId));

                if (!is_int($matchedLookupId) || $matchedLookupId <= 0) {
                    return $property;
                }

                $stats = (array) ($reviewStatsByLookupId->get($matchedLookupId) ?? []);
                $resolvedRating = max(0.0, (float) ($stats['rating'] ?? 0));
                $resolvedReviewCount = max(0, (int) ($stats['reviews_count'] ?? 0));

                $property->rating = $resolvedRating;
                $property->average_rating = $resolvedRating;
                $property->reviews_count = $resolvedReviewCount;
                $property->rating_count = $resolvedReviewCount;

                return $property;
            })->values();

            if ($minRating > 0 || $minReviews > 0) {
                $catalogProperties = $catalogProperties->filter(static function ($property) use ($minRating, $minReviews): bool {
                    $resolvedRating = max(0.0, (float) ($property->rating ?? $property->average_rating ?? 0));
                    $resolvedReviewCount = max(0, (int) ($property->reviews_count ?? $property->rating_count ?? 0));

                    if ($minRating > 0 && $resolvedRating < $minRating) {
                        return false;
                    }
                    if ($minReviews > 0 && $resolvedReviewCount < $minReviews) {
                        return false;
                    }

                    return true;
                })->values();
            }

            if ($sort === 'highest_reviews') {
                $catalogProperties = $catalogProperties
                    ->sortByDesc(static fn ($property) => (float) ($property->rating ?? $property->average_rating ?? 0))
                    ->values();
            }
        }

        if (Schema::hasTable('vendor_listing_media') && $propertyLookupIds->isNotEmpty()) {
            $mediaEntityTypeMap = [
                'accommodation' => ['property'],
                'liveaboard' => ['liveaboard', 'property', 'service'],
                'sea_transport' => ['sea_transport', 'sea-transport', 'marine_transport', 'transport', 'property', 'service'],
                'land_transport' => ['land_transport', 'land-transport', 'transport', 'property', 'service'],
                'vehicle_rental' => ['vehicle_rental', 'vehicle-rental', 'vehicle', 'transport', 'property', 'service'],
                'conference_room' => ['conference_room', 'conference-room', 'meeting_room', 'meeting-room', 'property', 'service'],
                'remote_workspace' => ['remote_workspace', 'remote-workspace', 'workspace', 'property', 'service'],
                'water_sports' => ['water_sports', 'water-sports', 'activity', 'property', 'service'],
                'excursion' => ['excursion', 'activity', 'property', 'service'],
                'restaurant' => ['restaurant', 'property', 'service'],
                'resort_day_visit' => ['resort_day_visit', 'resort-day-visit', 'property', 'service'],
            ];
            $mediaEntityTypes = $mediaEntityTypeMap[$dbCategoryKey] ?? [$dbCategoryKey];
            $mediaVendorUserIds = $catalogProperties
                ->pluck('vendor_user_id')
                ->map(static fn ($id) => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->unique()
                ->values();
            $mediaRows = collect(Cache::remember(
                'catalog:property_media:v4:' . md5($dbCategoryKey . ':' . implode('|', $mediaEntityTypes) . ':' . $propertyLookupIds->implode(',') . ':' . $mediaVendorUserIds->implode(',')),
                now()->addMinutes(3),
                static function () use ($propertyLookupIds, $mediaEntityTypes, $mediaVendorUserIds) {
                    $query = DB::table('vendor_listing_media')
                        ->whereIn('entity_type', $mediaEntityTypes)
                        ->whereIn('entity_id', $propertyLookupIds->all());

                    if ($mediaVendorUserIds->isNotEmpty()) {
                        $query->whereIn('vendor_user_id', $mediaVendorUserIds->all());
                    }

                    return $query
                        ->orderByDesc('is_primary')
                        ->orderByDesc('created_at')
                        ->limit(360)
                        ->get()
                        ->all();
                }
            ));

            $mediaByEntityId = $mediaRows->groupBy(static fn ($media) => (int) ($media->entity_id ?? 0));
            $catalogPropertyMediaByProperty = $catalogProperties
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

        $atollOptions = VendorPropertyCompatibilityReader::distinctOptionValues($dbCategoryKey, 'atoll', 120);
        $islandOptions = VendorPropertyCompatibilityReader::distinctOptionValues($dbCategoryKey, 'island', 120);

        {
            $transportDestinationOptions = collect(Cache::remember(
                'catalog:transport_destination_options:v1',
                now()->addMinutes(5),
                static function () {
                    $transportDestinationMap = [];
                    $transportLocationColumns = ['pickup_location', 'dropoff_location', 'origin_point', 'destination_point', 'island', 'city', 'atoll'];

                    foreach (['vendor_sea_transport_listings', 'vendor_land_transport_listings'] as $transportTable) {
                        if (!Schema::hasTable($transportTable)) {
                            continue;
                        }

                        $transportSelectCols = array_values(array_filter(
                            $transportLocationColumns,
                            static fn ($col) => Schema::hasColumn($transportTable, $col)
                        ));

                        if (empty($transportSelectCols)) {
                            continue;
                        }

                        $transportRows = DB::table($transportTable)
                            ->where('status', 'active')
                            ->when(Schema::hasColumn($transportTable, 'listing_moderation_status'), fn ($q) => $q->where('listing_moderation_status', 'approved'))
                            ->select($transportSelectCols)
                            ->limit(1000)
                            ->get();

                        foreach ($transportRows as $row) {
                            foreach ($transportLocationColumns as $col) {
                                if (!property_exists($row, $col)) {
                                    continue;
                                }
                                $val = trim((string) ($row->{$col} ?? ''));
                                if ($val === '') {
                                    continue;
                                }
                                $transportDestinationMap[strtolower($val)] = $val;
                            }
                        }
                    }

                    if (empty($transportDestinationMap)) {
                        return [];
                    }

                    natcasesort($transportDestinationMap);

                    return array_values($transportDestinationMap);
                }
            ))->values();
        }
    }

    return view('customer-category-catalog', [
        'apiBase' => workationApiBase(),
        'categoryKey' => $categoryKey,
        'categoryMeta' => $categoryMap[$categoryKey],
        'catalogProperties' => $catalogProperties,
        'catalogPropertyMediaByProperty' => $catalogPropertyMediaByProperty,
        'atollOptions' => $atollOptions,
        'islandOptions' => $islandOptions,
        'transportDestinationOptions' => $transportDestinationOptions,
        'visitorResidency' => $visitorResidency,
        'mvrUsdRate' => $mvrUsdRate,
        'filters' => [
            'q' => $queryText,
            'atoll' => $atollFilter,
            'island' => $islandFilter,
            'current_island' => $currentIsland,
            'pickup_island' => $pickupIsland,
            'reservation_datetime' => $reservationDatetime,
            'party_size' => $partySize,
            'vehicle_kind' => $vehicleKind,
            'activity_type' => $activityType,
            'difficulty' => $difficulty,
            'excursion_date' => $excursionDate,
            'workspace_type_filter' => $workspaceTypeFilter,
            'internet_speed' => $internetSpeed,
            'workspace_start' => $workspaceStart,
            'workspace_end' => $workspaceEnd,
            'time_slot' => $timeSlot,
            'facility_type' => $facilityType,
            'visit_date' => $visitDate,
            'conference_event_type' => $conferenceEventType,
            'conference_capacity' => $conferenceCapacity,
            'conference_date' => $conferenceDate,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'min_rating' => $minRating,
            'min_reviews' => $minReviews,
            'distance_km' => $distanceKm,
            'user_lat' => $userLat,
            'user_lng' => $userLng,
            'amenities' => $amenitiesQuery,
            'availability_only' => $availabilityOnly ? '1' : '',
            'sort' => $sort,
            'checkin' => trim((string) $request->query('checkin', '')),
            'checkout' => trim((string) $request->query('checkout', '')),
            'adults' => (int) $request->query('adults', 2),
            'children' => (int) $request->query('children', 0),
            'rooms' => (int) $request->query('rooms', 1),
            'origin_point' => trim((string) $request->query('origin_point', '')),
            'destination_point' => trim((string) $request->query('destination_point', '')),
            'trip_type' => $tripTypeFilter,
            'guest_type' => $guestTypeFilter,
            'travel_date' => $travelDate,
            'seats' => $seatsRequested,
            'start_point' => $liveaboardStartPoint,
            'end_point' => $liveaboardEndPoint,
            'journey_date' => $liveaboardDate,
            'travel_date' => trim((string) $request->query('travel_date', '')),
            'return_date' => trim((string) $request->query('return_date', '')),
            'pickup_date' => trim((string) $request->query('pickup_date', '')),
            'vehicle_type' => trim((string) $request->query('vehicle_type', '')), 
            'transmission' => $landTransmission,
            'supplier' => $landSupplier,
            'pickup_datetime' => $pickupDatetime,
            'dropoff_datetime' => $dropoffDatetime,
        ],
    ]);
});

Route::get('/sea-transport/{id}', function (Request $request, int $id) use ($resolveReviewStats) {
    $stTable = \App\Support\VendorPropertyCompatibilityReader::categoryTableNameFor('sea_transport');
    $propertyQuery = DB::table($stTable)
        ->where(function ($query) use ($id) {
            $query->where('vendor_property_id', $id)
                ->orWhere('id', $id);
        })
        ->where('status', 'active');

    if (Schema::hasColumn($stTable, 'listing_moderation_status')) {
        $propertyQuery->where('listing_moderation_status', 'approved');
    }

    $property = $propertyQuery->first();

    if (!$property) {
        abort(404);
    }

    $seaLookupIds = collect(workationPropertyLookupIds($property))
        ->map(static fn ($value) => (int) $value)
        ->filter(static fn (int $value): bool => $value > 0)
        ->unique()
        ->values();
    $seaReviewStats = $resolveReviewStats($seaLookupIds->all(), 'sea_transport', (int) ($property->vendor_user_id ?? 0));
    $seaMatchedLookupId = $seaLookupIds->first(static fn (int $lookupId): bool => $seaReviewStats->has($lookupId));
    if (is_int($seaMatchedLookupId) && $seaMatchedLookupId > 0) {
        $seaResolvedStats = (array) ($seaReviewStats->get($seaMatchedLookupId) ?? []);
        $seaResolvedRating = max(0.0, (float) ($seaResolvedStats['rating'] ?? 0));
        $seaResolvedReviewCount = max(0, (int) ($seaResolvedStats['reviews_count'] ?? 0));
        $property->rating = $seaResolvedRating;
        $property->average_rating = $seaResolvedRating;
        $property->reviews_count = $seaResolvedReviewCount;
        $property->rating_count = $seaResolvedReviewCount;
    }

    $rawDetails = $property->listing_details ?? '{}';
    $listingDetails = is_string($rawDetails) ? (json_decode($rawDetails, true) ?? []) : (array) $rawDetails;
    $routeSchedules = is_array($listingDetails['route_schedules'] ?? null) ? $listingDetails['route_schedules'] : [];
    $stopSequence   = is_array($listingDetails['stop_sequence'] ?? null) ? $listingDetails['stop_sequence'] : [];

    // Resolve "from" price — minimum leg local_adult, fallback to listing local_price.
    $fromPriceLocal   = (float) ($listingDetails['local_price'] ?? $listingDetails['local_adult'] ?? 0);
    $fromPriceForeign = (float) ($listingDetails['foreign_price'] ?? $listingDetails['foreign_adult'] ?? 0);
    foreach ($routeSchedules as $leg) {
        $legLocal   = isset($leg['local_adult'])   && $leg['local_adult']   !== null ? (float) $leg['local_adult']   : 0;
        $legForeign = isset($leg['foreign_adult']) && $leg['foreign_adult'] !== null ? (float) $leg['foreign_adult'] : 0;
        if ($legLocal   > 0 && ($fromPriceLocal   <= 0 || $legLocal   < $fromPriceLocal))   { $fromPriceLocal   = $legLocal; }
        if ($legForeign > 0 && ($fromPriceForeign <= 0 || $legForeign < $fromPriceForeign)) { $fromPriceForeign = $legForeign; }
    }

    // Vessel gallery (primary + additional photos).
    $galleryMedia = [];
    if (Schema::hasTable('vendor_listing_media')) {
        $mediaEntityIds = array_values(array_unique(array_filter([
            (int) ($property->id ?? 0),
            (int) ($property->vendor_property_id ?? 0),
        ], static fn (int $id): bool => $id > 0)));
        $mediaQuery = DB::table('vendor_listing_media')
            ->whereIn('entity_type', ['sea_transport', 'transport', 'marine_transport', 'sea-transport'])
            ->whereIn('entity_id', $mediaEntityIds);

        if (Schema::hasColumn('vendor_listing_media', 'vendor_user_id')) {
            $mediaQuery->where('vendor_user_id', (int) ($property->vendor_user_id ?? 0));
        }

        // Do not hard-filter by vendor_property_id because many rows only set entity_id.

        $mediaRows = $mediaQuery
            ->orderByRaw("CASE WHEN is_primary = true THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();

        foreach ($mediaRows as $mediaRow) {
            $mediaId = (int) ($mediaRow->id ?? 0);
            $candidateStoredValues = [];
            // Use /media/vendor/{id}/{variant} route format directly
            if ($mediaId > 0) {
                $candidateStoredValues[] = '/media/vendor/' . $mediaId . '/banner';
                $candidateStoredValues[] = '/media/vendor/' . $mediaId . '/thumb';
            }
            $candidateStoredValues[] = trim((string) ($mediaRow->file_path ?? ''));

            foreach ($candidateStoredValues as $rawPathCandidate) {
                $rawPath = trim((string) $rawPathCandidate);
                if ($rawPath === '') {
                    continue;
                }

                $resolved = function_exists('portalManagedMediaUrlFromPath')
                    ? portalManagedMediaUrlFromPath($rawPath)
                    : null;

                if (($resolved === null || trim((string) $resolved) === '') && function_exists('vendorMediaStorageUrlFromPath')) {
                    $resolved = vendorMediaStorageUrlFromPath($rawPath);
                }

                if ($resolved === null || trim((string) $resolved) === '') {
                    if (str_starts_with($rawPath, 'http://')) {
                        $resolved = 'https://' . ltrim(substr($rawPath, 7), '/');
                    } elseif (str_starts_with($rawPath, 'https://') || str_starts_with($rawPath, '/media/') || str_starts_with($rawPath, '/storage/')) {
                        $resolved = $rawPath;
                    } elseif (str_starts_with($rawPath, '__public__/')) {
                        $localPath = ltrim(substr($rawPath, strlen('__public__/')), '/');
                        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $localPath)));
                        $resolved = '/media/portal-public/' . $encodedPath;
                    } else {
                        $normalizedPath = ltrim(str_replace('\\', '/', $rawPath), '/');
                        $normalizedPath = preg_replace('#^(public/|storage/)#', '', $normalizedPath);
                        $resolved = '/storage/' . ltrim((string) $normalizedPath, '/');
                    }
                }

                if (is_string($resolved) && trim($resolved) !== '') {
                    $galleryMedia[] = trim($resolved);
                }
            }
        }

        $galleryMedia = array_values(array_unique(array_filter($galleryMedia, static fn ($url): bool => is_string($url) && trim($url) !== '')));

        if ($galleryMedia === []) {
            foreach (['gallery_media', 'gallery_images', 'gallery', 'images', 'media_urls', 'media'] as $mediaKey) {
                $mediaValue = $listingDetails[$mediaKey] ?? null;
                if (is_string($mediaValue)) {
                    $decoded = json_decode($mediaValue, true);
                    $mediaValue = is_array($decoded) ? $decoded : [$mediaValue];
                }
                if (!is_array($mediaValue)) {
                    continue;
                }

                foreach ($mediaValue as $candidateUrl) {
                    if (!is_string($candidateUrl)) {
                        continue;
                    }

                    $candidateUrl = trim($candidateUrl);
                    if ($candidateUrl === '') {
                        continue;
                    }

                    $resolved = function_exists('portalManagedMediaUrlFromPath')
                        ? (portalManagedMediaUrlFromPath($candidateUrl) ?? $candidateUrl)
                        : $candidateUrl;

                    if ($resolved !== '' && !in_array($resolved, $galleryMedia, true)) {
                        $galleryMedia[] = $resolved;
                    }
                }

                if ($galleryMedia !== []) {
                    break;
                }
            }

            $galleryMedia = array_values(array_unique(array_filter($galleryMedia, static fn ($url): bool => is_string($url) && trim($url) !== '')));
        }
    }
    $heroUrl = $galleryMedia[0] ?? '';
    if ($heroUrl === '') {
        $fallbackHero = trim((string) (
            $listingDetails['image_url']
            ?? $listingDetails['featured_image']
            ?? $listingDetails['banner_image']
            ?? ''
        ));
        if ($fallbackHero !== '') {
            $heroUrl = function_exists('portalManagedMediaUrlFromPath')
                ? (portalManagedMediaUrlFromPath($fallbackHero) ?? $fallbackHero)
                : $fallbackHero;
            $galleryMedia = [$heroUrl];
        }
    }

    // Operator / vendor.
    $vendor = DB::table('users')->where('id', $property->vendor_user_id ?? 0)->first();

    $visitorResidency = function_exists('workationDetectVisitorResidency')
        ? workationDetectVisitorResidency($request)
        : (strtoupper(trim((string) ($request->header('CF-IPCountry') ?? $request->header('X-Country-Code') ?? ''))) === 'MV' ? 'local_resident' : 'foreign_national');

    $mvrUsdRate = max(0.0, (float) env('MVR_USD_RATE', 15.42));

    return view('sea-transport-detail', [
        'property'          => $property,
        'listingDetails'    => $listingDetails,
        'routeSchedules'    => $routeSchedules,
        'stopSequence'      => $stopSequence,
        'fromPriceLocal'    => $fromPriceLocal,
        'fromPriceForeign'  => $fromPriceForeign,
        'heroUrl'           => $heroUrl,
        'galleryMedia'      => $galleryMedia,
        'vendor'            => $vendor,
        'visitorResidency'  => $visitorResidency,
        'mvrUsdRate'        => $mvrUsdRate,
    ]);
});

Route::get('/liveaboard/{id}', function (Request $request, int $id) use ($resolveReviewStats) {
    $laTable = \App\Support\VendorPropertyCompatibilityReader::categoryTableNameFor('liveaboard');
    $propertyQuery = DB::table($laTable)
        ->where(function ($query) use ($id) {
            $query->where('vendor_property_id', $id)
                ->orWhere('id', $id);
        })
        ->where('status', 'active');

    if (Schema::hasColumn($laTable, 'listing_moderation_status')) {
        $propertyQuery->where('listing_moderation_status', 'approved');
    }

    $property = $propertyQuery->first();

    if (!$property) {
        abort(404);
    }

    $liveaboardLookupIds = collect(workationPropertyLookupIds($property))
        ->map(static fn ($value) => (int) $value)
        ->filter(static fn (int $value): bool => $value > 0)
        ->unique()
        ->values();
    $liveaboardReviewStats = $resolveReviewStats($liveaboardLookupIds->all(), 'liveaboard', (int) ($property->vendor_user_id ?? 0));
    $liveaboardMatchedLookupId = $liveaboardLookupIds->first(static fn (int $lookupId): bool => $liveaboardReviewStats->has($lookupId));
    if (is_int($liveaboardMatchedLookupId) && $liveaboardMatchedLookupId > 0) {
        $liveaboardResolvedStats = (array) ($liveaboardReviewStats->get($liveaboardMatchedLookupId) ?? []);
        $liveaboardResolvedRating = max(0.0, (float) ($liveaboardResolvedStats['rating'] ?? 0));
        $liveaboardResolvedReviewCount = max(0, (int) ($liveaboardResolvedStats['reviews_count'] ?? 0));
        $property->rating = $liveaboardResolvedRating;
        $property->average_rating = $liveaboardResolvedRating;
        $property->reviews_count = $liveaboardResolvedReviewCount;
        $property->rating_count = $liveaboardResolvedReviewCount;
    }

    $rawDetails = $property->listing_details ?? '{}';
    $listingDetails = is_string($rawDetails) ? (json_decode($rawDetails, true) ?? []) : (array) $rawDetails;
    $stopovers = is_array($listingDetails['stopovers'] ?? null) ? $listingDetails['stopovers'] : [];
    $pricingMatrix = is_array($listingDetails['pricing_matrix'] ?? null) ? $listingDetails['pricing_matrix'] : [];

    // Resolve minimum price from pricing matrix
    $minPrice = count($pricingMatrix) > 0 ? min(array_values($pricingMatrix)) : 0;

    // Gallery media
    $galleryMedia = [];
    if (Schema::hasTable('vendor_listing_media')) {
        $mediaEntityIds = array_values(array_unique(array_filter([
            (int) ($property->id ?? 0),
            (int) ($property->vendor_property_id ?? 0),
        ], static fn (int $id): bool => $id > 0)));
        $liveaboardMediaTypes = ['liveaboard', 'property', 'service'];
        $mediaQuery = DB::table('vendor_listing_media')
            ->whereIn('entity_type', $liveaboardMediaTypes)
            ->whereIn('entity_id', $mediaEntityIds);
        $liveaboardVendorUserId = (int) ($property->vendor_user_id ?? 0);
        if ($liveaboardVendorUserId > 0) {
            $mediaQuery->where('vendor_user_id', $liveaboardVendorUserId);
        }
        $mediaRows = $mediaQuery
            ->orderByRaw("CASE WHEN is_primary = true THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get();

        foreach ($mediaRows as $mediaRow) {
            $mediaId = (int) ($mediaRow->id ?? 0);
            $candidateStoredValues = [];
            // Use /media/vendor/{id}/{variant} route format directly
            if ($mediaId > 0) {
                $candidateStoredValues[] = '/media/vendor/' . $mediaId . '/banner';
                $candidateStoredValues[] = '/media/vendor/' . $mediaId . '/thumb';
            }
            $candidateStoredValues[] = trim((string) ($mediaRow->file_path ?? ''));

            foreach ($candidateStoredValues as $rawPathCandidate) {
                $rawPath = trim((string) $rawPathCandidate);
                if ($rawPath === '') {
                    continue;
                }

                $resolved = function_exists('portalManagedMediaUrlFromPath')
                    ? portalManagedMediaUrlFromPath($rawPath)
                    : null;

                if (($resolved === null || trim((string) $resolved) === '') && function_exists('vendorMediaStorageUrlFromPath')) {
                    $resolved = vendorMediaStorageUrlFromPath($rawPath);
                }

                if ($resolved === null || trim((string) $resolved) === '') {
                    if (str_starts_with($rawPath, 'http://')) {
                        $resolved = 'https://' . ltrim(substr($rawPath, 7), '/');
                    } elseif (str_starts_with($rawPath, 'https://') || str_starts_with($rawPath, '/media/') || str_starts_with($rawPath, '/storage/')) {
                        $resolved = $rawPath;
                    } elseif (str_starts_with($rawPath, '__public__/')) {
                        $localPath = ltrim(substr($rawPath, strlen('__public__/')), '/');
                        $encodedPath = implode('/', array_map('rawurlencode', explode('/', $localPath)));
                        $resolved = '/media/portal-public/' . $encodedPath;
                    } else {
                        $normalizedPath = ltrim(str_replace('\\', '/', $rawPath), '/');
                        $normalizedPath = preg_replace('#^(public/|storage/)#', '', $normalizedPath);
                        $resolved = '/storage/' . ltrim((string) $normalizedPath, '/');
                    }
                }

                if (is_string($resolved) && trim($resolved) !== '') {
                    $galleryMedia[] = trim($resolved);
                }
            }
        }

        $galleryMedia = array_values(array_unique(array_filter($galleryMedia, static fn ($url): bool => is_string($url) && trim($url) !== '')));

        if ($galleryMedia === []) {
            foreach (['gallery_media', 'gallery_images', 'gallery', 'images', 'media_urls', 'media'] as $mediaKey) {
                $mediaValue = $listingDetails[$mediaKey] ?? null;
                if (is_string($mediaValue)) {
                    $decoded = json_decode($mediaValue, true);
                    $mediaValue = is_array($decoded) ? $decoded : [$mediaValue];
                }
                if (!is_array($mediaValue)) {
                    continue;
                }

                foreach ($mediaValue as $candidateUrl) {
                    if (!is_string($candidateUrl)) {
                        continue;
                    }

                    $candidateUrl = trim($candidateUrl);
                    if ($candidateUrl === '') {
                        continue;
                    }

                    $resolved = function_exists('portalManagedMediaUrlFromPath')
                        ? (portalManagedMediaUrlFromPath($candidateUrl) ?? $candidateUrl)
                        : $candidateUrl;

                    if ($resolved !== '' && !in_array($resolved, $galleryMedia, true)) {
                        $galleryMedia[] = $resolved;
                    }
                }

                if ($galleryMedia !== []) {
                    break;
                }
            }
        }
    }

    $heroUrl = $galleryMedia[0] ?? '';
    if ($heroUrl === '') {
        $fallbackHero = trim((string) (
            $listingDetails['image_url']
            ?? $listingDetails['featured_image']
            ?? $listingDetails['banner_image']
            ?? ''
        ));
        if ($fallbackHero !== '') {
            $heroUrl = function_exists('portalManagedMediaUrlFromPath')
                ? (portalManagedMediaUrlFromPath($fallbackHero) ?? $fallbackHero)
                : $fallbackHero;
            $galleryMedia = [$heroUrl];
        }
    }

    // Vendor/operator
    $vendor = DB::table('users')->where('id', $property->vendor_user_id ?? 0)->first();

    $visitorResidency = function_exists('workationDetectVisitorResidency')
        ? workationDetectVisitorResidency($request)
        : (strtoupper(trim((string) ($request->header('CF-IPCountry') ?? $request->header('X-Country-Code') ?? ''))) === 'MV' ? 'local_resident' : 'foreign_national');

    $mvrUsdRate = max(0.0, (float) env('MVR_USD_RATE', 15.42));

    // Query rooms for this liveaboard property
    $propertyId = (int) ($property->id ?? 0);
    $vendorUserId = (int) ($property->vendor_user_id ?? 0);
    
    $rooms = collect();
    $roomMediaByRoom = collect();
    
    if ($propertyId > 0 && Schema::hasTable('vendor_property_room_categories')) {
        $hasLegacyPropertyId = Schema::hasColumn('vendor_property_room_categories', 'property_id');

        $roomsQuery = DB::table('vendor_property_room_categories')
            ->where(function ($query) use ($propertyId, $vendorUserId, $hasLegacyPropertyId) {
                $query->where(function ($inner) use ($propertyId, $vendorUserId) {
                    $inner->where('vendor_property_id', $propertyId)
                        ->where('vendor_user_id', $vendorUserId);
                });

                if ($hasLegacyPropertyId) {
                    $query->orWhere(function ($inner) use ($propertyId, $vendorUserId) {
                        $inner->where('property_id', $propertyId)
                            ->where('vendor_user_id', $vendorUserId)
                            ->where('vendor_property_id', 0);
                    });
                }
            })
            ->orderBy('id')
            ->get();
        
        $rooms = collect($roomsQuery);
        
        // Query room media if table exists
        if (Schema::hasTable('vendor_listing_media') && $rooms->isNotEmpty()) {
            $roomIds = $rooms->pluck('id')->all();
            $mediaRows = DB::table('vendor_listing_media')
                ->whereIn('entity_type', ['room', 'cabin', 'room_category'])
                ->whereIn('entity_id', $roomIds)
                ->orderByRaw("CASE WHEN is_primary = true THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get();
            
            foreach ($mediaRows as $mediaRow) {
                $entityId = (int) ($mediaRow->entity_id ?? 0);
                if (!$roomMediaByRoom->has($entityId)) {
                    $roomMediaByRoom->put($entityId, collect());
                }
                
                $mediaUrl = '/media/vendor/' . ((int) ($mediaRow->id ?? 0)) . '/thumb';
                $roomMediaByRoom->get($entityId)->push($mediaUrl);
            }
        }
    }

    // Media URL resolver helper
    $mediaUrl = static function ($media, $variant = 'thumb') {
        if (!$media) {
            return null;
        }
        
        $mediaId = (int) ($media->id ?? 0);
        if ($mediaId > 0) {
            return '/media/vendor/' . $mediaId . '/' . $variant;
        }
        
        return null;
    };

    return view('liveaboard-detail', [
        'property'          => $property,
        'listingDetails'    => $listingDetails,
        'stopovers'         => $stopovers,
        'pricingMatrix'     => $pricingMatrix,
        'minPrice'          => $minPrice,
        'heroUrl'           => $heroUrl,
        'galleryMedia'      => $galleryMedia,
        'vendor'            => $vendor,
        'visitorResidency'  => $visitorResidency,
        'mvrUsdRate'        => $mvrUsdRate,
        'rooms'             => $rooms,
        'roomMediaByRoom'   => $roomMediaByRoom,
        'mediaUrl'          => $mediaUrl,
    ]);
});

// Generic detail page route handler for land-transport, vehicle-rental, conference-room, remote-workspace
foreach (['land-transport' => 'land_transport', 'vehicle-rental' => 'vehicle_rental', 'conference-room' => 'conference_room', 'remote-workspace' => 'remote_workspace'] as $routePath => $categoryKey) {
    Route::get('/' . $routePath . '/{id}', function (Request $request, int $id) use ($categoryKey, $resolveReviewStats) {
        $table = \App\Support\VendorPropertyCompatibilityReader::categoryTableNameFor($categoryKey);
        $propertyQuery = DB::table($table)
            ->where(function ($query) use ($id) {
                $query->where('vendor_property_id', $id)
                    ->orWhere('id', $id);
            })
            ->where('status', 'active');

        if (Schema::hasColumn($table, 'listing_moderation_status')) {
            $propertyQuery->where('listing_moderation_status', 'approved');
        }

        $property = $propertyQuery->first();

        if (!$property) {
            abort(404);
        }

        $genericLookupIds = collect(workationPropertyLookupIds($property))
            ->map(static fn ($value) => (int) $value)
            ->filter(static fn (int $value): bool => $value > 0)
            ->unique()
            ->values();
        $genericReviewStats = $resolveReviewStats($genericLookupIds->all(), $categoryKey, (int) ($property->vendor_user_id ?? 0));
        $genericMatchedLookupId = $genericLookupIds->first(static fn (int $lookupId): bool => $genericReviewStats->has($lookupId));
        if (is_int($genericMatchedLookupId) && $genericMatchedLookupId > 0) {
            $genericResolvedStats = (array) ($genericReviewStats->get($genericMatchedLookupId) ?? []);
            $genericResolvedRating = max(0.0, (float) ($genericResolvedStats['rating'] ?? 0));
            $genericResolvedReviewCount = max(0, (int) ($genericResolvedStats['reviews_count'] ?? 0));
            $property->rating = $genericResolvedRating;
            $property->average_rating = $genericResolvedRating;
            $property->reviews_count = $genericResolvedReviewCount;
            $property->rating_count = $genericResolvedReviewCount;
        }

        $rawDetails = $property->listing_details ?? '{}';
        $listingDetails = is_string($rawDetails) ? (json_decode($rawDetails, true) ?? []) : (array) $rawDetails;
        $pricingMatrix = is_array($listingDetails['pricing_matrix'] ?? null) ? $listingDetails['pricing_matrix'] : [];

        // Resolve minimum price from pricing matrix
        $minPrice = count($pricingMatrix) > 0 ? min(array_values($pricingMatrix)) : 0;

        // Gallery media
        $galleryMedia = [];
        if (Schema::hasTable('vendor_listing_media')) {
            $mediaEntityIds = array_values(array_unique(array_filter([
                (int) ($property->id ?? 0),
                (int) ($property->vendor_property_id ?? 0),
            ], static fn (int $id): bool => $id > 0)));
            $genericMediaTypeMap = [
                'land_transport' => ['land_transport', 'land-transport', 'transport', 'property', 'service'],
                'vehicle_rental' => ['vehicle_rental', 'vehicle-rental', 'transport', 'vehicle', 'property', 'service'],
                'conference_room' => ['conference_room', 'conference-room', 'meeting_room', 'meeting-room', 'property', 'service'],
                'remote_workspace' => ['remote_workspace', 'remote-workspace', 'workspace', 'property', 'service'],
            ];
            $mediaEntityTypes = $genericMediaTypeMap[$categoryKey] ?? [$categoryKey];
            $mediaQuery = DB::table('vendor_listing_media')
                ->whereIn('entity_type', $mediaEntityTypes)
                ->whereIn('entity_id', $mediaEntityIds);
            $genericVendorUserId = (int) ($property->vendor_user_id ?? 0);
            if ($genericVendorUserId > 0) {
                $mediaQuery->where('vendor_user_id', $genericVendorUserId);
            }
            $mediaRows = $mediaQuery
                ->orderByRaw("CASE WHEN is_primary = true THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get();

            foreach ($mediaRows as $mediaRow) {
                $mediaId = (int) ($mediaRow->id ?? 0);
                $candidateStoredValues = [];
                if ($mediaId > 0) {
                    $candidateStoredValues[] = '/media/vendor/' . $mediaId . '/banner';
                    $candidateStoredValues[] = '/media/vendor/' . $mediaId . '/thumb';
                }
                $candidateStoredValues[] = trim((string) ($mediaRow->file_path ?? ''));

                foreach ($candidateStoredValues as $rawPathCandidate) {
                    $rawPath = trim((string) $rawPathCandidate);
                    if ($rawPath === '') {
                        continue;
                    }

                    $resolved = function_exists('portalManagedMediaUrlFromPath')
                        ? portalManagedMediaUrlFromPath($rawPath)
                        : null;

                    if (($resolved === null || trim((string) $resolved) === '') && function_exists('vendorMediaStorageUrlFromPath')) {
                        $resolved = vendorMediaStorageUrlFromPath($rawPath);
                    }

                    if ($resolved === null || trim((string) $resolved) === '') {
                        if (str_starts_with($rawPath, 'http://')) {
                            $resolved = 'https://' . ltrim(substr($rawPath, 7), '/');
                        } elseif (str_starts_with($rawPath, 'https://') || str_starts_with($rawPath, '/media/') || str_starts_with($rawPath, '/storage/')) {
                            $resolved = $rawPath;
                        } elseif (str_starts_with($rawPath, '__public__/')) {
                            $localPath = ltrim(substr($rawPath, strlen('__public__/')), '/');
                            $encodedPath = implode('/', array_map('rawurlencode', explode('/', $localPath)));
                            $resolved = '/media/portal-public/' . $encodedPath;
                        } else {
                            $normalizedPath = ltrim(str_replace('\\', '/', $rawPath), '/');
                            $normalizedPath = preg_replace('#^(public/|storage/)#', '', $normalizedPath);
                            $resolved = '/storage/' . ltrim((string) $normalizedPath, '/');
                        }
                    }

                    if (is_string($resolved) && trim($resolved) !== '') {
                        $galleryMedia[] = trim($resolved);
                    }
                }
            }

            $galleryMedia = array_values(array_unique(array_filter($galleryMedia, static fn ($url): bool => is_string($url) && trim($url) !== '')));
        }

        // Determine view template based on category
        $viewMap = [
            'land_transport' => 'land-transport-detail',
            'vehicle_rental' => 'vehicle-rental-detail',
            'conference_room' => 'conference-room-detail',
            'remote_workspace' => 'remote-workspace-detail',
        ];
        $viewName = $viewMap[$categoryKey] ?? $categoryKey . '-detail';

        if ($categoryKey === 'land_transport') {
            return redirect('/category-booking/land-transport/' . ((int) ($property->vendor_property_id ?? $property->id ?? $id)));
        }

        return view($viewName, [
            'property'          => $property,
            'listingDetails'    => $listingDetails,
            'pricingMatrix'     => $pricingMatrix,
            'minPrice'          => $minPrice,
            'galleryMedia'      => $galleryMedia,
            'categoryKey'       => $categoryKey,
        ]);
    });
}