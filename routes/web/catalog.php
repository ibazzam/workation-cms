<?php

use App\Support\VendorPropertyCompatibilityReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Route::get('/catalog/{category}', function (Request $request, string $category) {
    $categoryMap = [
        'accommodation' => ['label' => 'Accommodation', 'subtitle' => 'Hotels, resorts, villas, and guesthouses.', 'hero_image_url' => ''],
        'marine-transport' => ['label' => 'Marine Transport', 'subtitle' => 'Speedboats, dhonis, and water transfers between islands.', 'hero_image_url' => ''],
        'land-transport' => ['label' => 'Land Transport', 'subtitle' => 'Cars, vans, and local ground transfers.', 'hero_image_url' => ''],
        'excursion' => ['label' => 'Excursion', 'subtitle' => 'Experiences, tours, and activity packages.', 'hero_image_url' => ''],
        'water_sports' => ['label' => 'Water Sports', 'subtitle' => 'Diving, snorkeling, and sea activity experiences.', 'hero_image_url' => ''],
        'remote_workspace' => ['label' => 'Remote Workspace', 'subtitle' => 'Work-friendly spaces and productivity stays.', 'hero_image_url' => ''],
        'conference_room' => ['label' => 'Conference & Meeting Spaces', 'subtitle' => 'Hotel conference rooms, halls, and meeting spaces for events, training, seminars.', 'hero_image_url' => ''],
        'resort_day_visit' => ['label' => 'Resort Day Visit', 'subtitle' => 'Day access offers for top resort properties.', 'hero_image_url' => ''],
        'restaurant' => ['label' => 'Restaurant', 'subtitle' => 'Island-specific dining - find restaurants on your island.', 'hero_image_url' => ''],
        'vehicle_rental' => ['label' => 'Vehicle Rental', 'subtitle' => 'Cars, bikes, speedboats, and private vessel hire by island.', 'hero_image_url' => ''],
    ];

    $categoryKey = strtolower(trim($category));
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
                $row->id = (int) ($row->vendor_property_id ?? $row->id ?? 0);
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

        // For accommodation, override base_price with the cheapest valid room price.
        // Room pricing may come from legacy vendor_property_room_categories or accommodation_rooms.
        if ($dbCategoryKey === 'accommodation' && $propertyLookupIds->isNotEmpty()) {
            $combinedRoomPricesByProperty = collect();

            $legacyRoomPropertyColumn = null;
            if (Schema::hasTable('vendor_property_room_categories')) {
                if (Schema::hasColumn('vendor_property_room_categories', 'vendor_property_id')) {
                    $legacyRoomPropertyColumn = 'vendor_property_id';
                } elseif (Schema::hasColumn('vendor_property_room_categories', 'property_id')) {
                    $legacyRoomPropertyColumn = 'property_id';
                }
            }

            if ($legacyRoomPropertyColumn !== null
            ) {
                $legacyPriceColumns = [];
                foreach ([
                    'base_price',
                    'meal_plan_room_only_price',
                    'meal_plan_bb_price',
                    'meal_plan_hb_price',
                    'meal_plan_fb_price',
                    'meal_plan_ai_price',
                    'meal_plan_breakfast_price',
                    'meal_plan_half_board_price',
                    'meal_plan_full_board_price',
                    'meal_plan_all_inclusive_price',
                ] as $candidateColumn) {
                    if (Schema::hasColumn('vendor_property_room_categories', $candidateColumn)) {
                        $legacyPriceColumns[] = $candidateColumn;
                    }
                }

                if (!empty($legacyPriceColumns)) {
                $legacyRoomPrices = DB::table('vendor_property_room_categories')
                    ->whereIn($legacyRoomPropertyColumn, $propertyLookupIds->all())
                    ->get(array_merge([$legacyRoomPropertyColumn], $legacyPriceColumns))
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

            if (Schema::hasTable('accommodation_packages')
                && Schema::hasColumn('accommodation_packages', 'property_id')) {
                $packagePriceColumns = ['property_id'];
                if (Schema::hasColumn('accommodation_packages', 'base_price')) {
                    $packagePriceColumns[] = 'base_price';
                }
                if (Schema::hasColumn('accommodation_packages', 'price_per_night')) {
                    $packagePriceColumns[] = 'price_per_night';
                }

                if (count($packagePriceColumns) > 1) {
                    $packageRowsQuery = DB::table('accommodation_packages as ap');

                    if (Schema::hasTable('accommodation_rooms')
                        && Schema::hasColumn('accommodation_packages', 'room_id')
                        && Schema::hasColumn('accommodation_rooms', 'id')
                        && Schema::hasColumn('accommodation_rooms', 'property_id')) {
                        $packageRowsQuery->leftJoin('accommodation_rooms as ar', 'ar.id', '=', 'ap.room_id')
                            ->where(static function ($query) use ($propertyLookupIds) {
                                $query->whereIn('ap.property_id', $propertyLookupIds->all())
                                    ->orWhereIn('ar.property_id', $propertyLookupIds->all());
                            });
                    } else {
                        $packageRowsQuery->whereIn('ap.property_id', $propertyLookupIds->all());
                    }

                    $packageRowsQuery->when(Schema::hasColumn('accommodation_packages', 'is_active'), static function ($query) {
                        $query->where(static function ($activeQuery) {
                            $activeQuery->where('ap.is_active', 1)
                                ->orWhereNull('ap.is_active');
                        });
                    });

                    $packageSelectColumns = [];
                    foreach ($packagePriceColumns as $column) {
                        $packageSelectColumns[] = 'ap.' . $column;
                    }
                    $packageSelectColumns[] = 'ar.property_id as room_property_id';

                    $packagePrices = $packageRowsQuery
                        ->get($packageSelectColumns)
                        ->groupBy(static function ($row) {
                            $directPropertyId = (int) ($row->property_id ?? 0);
                            if ($directPropertyId > 0) {
                                return $directPropertyId;
                            }

                            return (int) ($row->room_property_id ?? 0);
                        })
                        ->map(static function ($rows) {
                            return collect($rows)
                                ->map(static function ($row) {
                                    $perNight = isset($row->price_per_night) ? (float) ($row->price_per_night ?? 0) : 0;
                                    $base = isset($row->base_price) ? (float) ($row->base_price ?? 0) : 0;
                                    return $perNight > 0 ? $perNight : $base;
                                })
                                ->filter(static fn (float $value) => $value > 0)
                                ->min();
                        })
                        ->filter(static fn ($value) => is_numeric($value) && (float) $value > 0);

                    $combinedRoomPricesByProperty = $combinedRoomPricesByProperty
                        ->merge($packagePrices)
                        ->groupBy(static fn ($value, $key) => (int) $key)
                        ->map(static function ($values) {
                            return collect($values)
                                ->map(static fn ($value) => (float) $value)
                                ->filter(static fn (float $value) => $value > 0)
                                ->min();
                        })
                        ->filter(static fn ($value) => is_numeric($value) && (float) $value > 0);
                }
            }

            if (Schema::hasTable('accommodation_rooms') && Schema::hasColumn('accommodation_rooms', 'property_id')) {
                $hasRoomActiveColumn = Schema::hasColumn('accommodation_rooms', 'is_active');
                $hasNightlyColumn = Schema::hasColumn('accommodation_rooms', 'base_price_per_night');
                $hasLegacyRoomPriceColumn = Schema::hasColumn('accommodation_rooms', 'base_price');

                if ($hasNightlyColumn || $hasLegacyRoomPriceColumn) {
                    $roomPriceColumns = ['property_id'];
                    if ($hasNightlyColumn) {
                        $roomPriceColumns[] = 'base_price_per_night';
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
                                    $legacy = isset($row->base_price) ? (float) $row->base_price : 0;
                                    return $nightly > 0 ? $nightly : $legacy;
                                })
                                ->filter(static fn (float $value) => $value > 0)
                                ->min();
                        })
                        ->filter(static fn ($value) => is_numeric($value) && (float) $value > 0);

                    $combinedRoomPricesByProperty = $combinedRoomPricesByProperty
                        ->merge($canonicalRoomPrices)
                        ->groupBy(static fn ($value, $key) => (int) $key)
                        ->map(static function ($values) {
                            return collect($values)
                                ->map(static fn ($value) => (float) $value)
                                ->filter(static fn (float $value) => $value > 0)
                                ->min();
                        })
                        ->filter(static fn ($value) => is_numeric($value) && (float) $value > 0);
                }
            }

            // Force accommodation pricing to come only from room/package tables.
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

        if (Schema::hasTable('vendor_listing_media') && $propertyLookupIds->isNotEmpty()) {
            $mediaRows = collect(Cache::remember(
                'catalog:property_media:v1:' . md5($propertyLookupIds->implode(',')),
                now()->addMinutes(3),
                static function () use ($propertyLookupIds) {
                    return DB::table('vendor_listing_media')
                        ->where('entity_type', 'property')
                        ->whereIn('entity_id', $propertyLookupIds->all())
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

                    foreach (['vendor_marine_transport_listings', 'vendor_land_transport_listings'] as $transportTable) {
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
            'travel_date' => trim((string) $request->query('travel_date', '')),
            'return_date' => trim((string) $request->query('return_date', '')),
            'pickup_date' => trim((string) $request->query('pickup_date', '')),
            'vehicle_type' => trim((string) $request->query('vehicle_type', '')), 
        ],
    ]);
});
