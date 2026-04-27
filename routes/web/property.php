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

Route::get('/property/{property}', function (Request $request, int $property) {
    $propertyRow = VendorPropertyCompatibilityReader::loadPropertyById($property);

    if (!$propertyRow) {
        abort(404);
    }

    $listingStatus = strtolower(trim((string) ($propertyRow->status ?? 'inactive')));
    if ($listingStatus !== 'active') {
        abort(404);
    }

    $listingCategory = strtolower(trim((string) ($propertyRow->listing_category ?? '')));
    if ($listingCategory === 'accommodation' && Schema::hasTable('vendor_accommodation_listings')) {
        $dedicatedAccommodationRows = VendorPropertyCompatibilityReader::loadAccommodationRows(
            collect([(int) $propertyRow->id])
        );

        if ($dedicatedAccommodationRows->isNotEmpty()) {
            $mergedRows = VendorPropertyCompatibilityReader::mergeAccommodationFromDedicated(
                collect([$propertyRow]),
                $dedicatedAccommodationRows,
                'property_profile'
            );

            $propertyRow = $mergedRows->first() ?: $propertyRow;
        }
    }

    $rooms = collect(Cache::remember(
        'property_profile:rooms:v1:' . (int) $propertyRow->id,
        now()->addMinutes(3),
        static function () use ($propertyRow) {
            $loadedRooms = collect();
            $legacyRoomPropertyColumn = null;
            if (Schema::hasTable('vendor_property_room_categories')) {
                if (Schema::hasColumn('vendor_property_room_categories', 'vendor_property_id')) {
                    $legacyRoomPropertyColumn = 'vendor_property_id';
                } elseif (Schema::hasColumn('vendor_property_room_categories', 'property_id')) {
                    $legacyRoomPropertyColumn = 'property_id';
                }
            }

            if ($legacyRoomPropertyColumn !== null) {
                $loadedRooms = DB::table('vendor_property_room_categories')
                    ->where($legacyRoomPropertyColumn, (int) $propertyRow->id)
                    ->orderByDesc('updated_at')
                    ->limit(60)
                    ->get();
            }

            if ($loadedRooms->isEmpty() && Schema::hasTable('accommodation_rooms')) {
                $accommodationRoomRows = DB::table('accommodation_rooms')
                    ->where('property_id', (int) $propertyRow->id)
                    ->when(Schema::hasColumn('accommodation_rooms', 'is_active'), static function ($query) {
                        $query->where('is_active', 1);
                    })
                    ->orderByDesc('updated_at')
                    ->limit(60)
                    ->get();

                $loadedRooms = $accommodationRoomRows->map(static function ($room) {
                    $roomType = trim((string) ($room->room_type ?? 'Room'));
                    $roomName = trim((string) ($room->name ?? ''));
                    if ($roomName === '') {
                        $roomName = $roomType !== '' ? ucfirst(str_replace('_', ' ', $roomType)) : 'Room';
                    }

                    $room->name = $roomName;
                    $room->base_price = isset($room->base_price) ? (float) $room->base_price : (float) ($room->base_price_per_night ?? 0);
                    $room->max_occupancy = isset($room->max_occupancy) ? (int) $room->max_occupancy : (int) ($room->capacity_guests ?? 2);
                    $room->amenities = (string) ($room->amenities ?? '');
                    $room->bathroom_amenities = (string) ($room->bathroom_amenities ?? '');
                    $room->bed_type = (string) ($room->bed_type ?? 'Standard Bed');
                    return $room;
                })->values();
            }

            return $loadedRooms->values()->all();
        }
    ));

    $roomIds = $rooms->pluck('id')->map(static fn ($id) => (int) $id)->filter(static fn (int $id) => $id > 0)->values();
    $mediaPayload = Cache::remember(
        'property_profile:media:v1:' . (int) $propertyRow->id . ':' . md5($roomIds->implode(',')),
        now()->addMinutes(3),
        static function () use ($propertyRow, $roomIds) {
            $payload = [
                'property_media' => [],
                'room_media_rows' => [],
            ];

            if (!Schema::hasTable('vendor_listing_media')) {
                return $payload;
            }

            $propertyMediaEntityIds = collect([
                (int) ($propertyRow->id ?? 0),
                (int) ($propertyRow->dedicated_row_id ?? 0),
            ])->filter(static fn (int $id) => $id > 0)->unique()->values();

            $mediaQuery = DB::table('vendor_listing_media');
            $mediaQuery->where(function ($query) use ($propertyMediaEntityIds, $roomIds) {
                $query->orWhere(function ($inner) use ($propertyMediaEntityIds) {
                    $inner->where('entity_type', 'property')->whereIn('entity_id', $propertyMediaEntityIds->all());
                });

                if ($roomIds->isNotEmpty()) {
                    $query->orWhere(function ($inner) use ($roomIds) {
                        $inner->where('entity_type', 'room')->whereIn('entity_id', $roomIds->all());
                    });
                }
            });

            $mediaRows = $mediaQuery->orderByDesc('is_primary')->orderByDesc('created_at')->limit(300)->get();
            $payload['property_media'] = $mediaRows
                ->filter(static fn ($m) => strtolower((string) ($m->entity_type ?? '')) === 'property')
                ->values()
                ->all();
            $payload['room_media_rows'] = $mediaRows
                ->filter(static fn ($m) => strtolower((string) ($m->entity_type ?? '')) === 'room')
                ->values()
                ->all();

            return $payload;
        }
    );

    $propertyMedia = collect((array) ($mediaPayload['property_media'] ?? []))->values();
    $roomMediaByRoom = collect((array) ($mediaPayload['room_media_rows'] ?? []))
        ->groupBy(static fn ($m) => (int) ($m->entity_id ?? 0));

    $mediaUrl = static function ($media, string $variant = 'banner'): ?string {
        $filePath = trim((string) ($media->file_path ?? ''));
        if ($filePath !== '' && (str_starts_with($filePath, 'http://') || str_starts_with($filePath, 'https://'))) {
            $resolved = $filePath;
            $resolved = trim((string) $resolved);
            if ($resolved !== '') {
                if (str_starts_with($resolved, 'http://')) {
                    $resolved = 'https://' . ltrim(substr($resolved, 7), '/');
                }

                return $resolved;
            }
        }

        $mediaId = (int) ($media->id ?? 0);
        if ($mediaId > 0) {
            return '/media/vendor/' . $mediaId . '/' . $variant;
        }

        return null;
    };

    $details = json_decode((string) ($propertyRow->listing_details ?? ''), true);
    if (!is_array($details)) {
        $details = [];
    }

    $currentListingCategory = str_replace('-', '_', strtolower(trim((string) ($propertyRow->listing_category ?? ''))));
    if ($currentListingCategory === 'accommodation') {
        $details = (array) Cache::remember(
            'property_profile:details:v1:' . (int) $propertyRow->id . ':' . md5((string) ($propertyRow->listing_details ?? '')),
            now()->addMinutes(3),
            static function () use ($propertyRow, $details) {
                $cachedDetails = $details;

                if (Schema::hasTable('vendor_accommodation_transfer_rates')) {
                    $transferRows = DB::table('vendor_accommodation_transfer_rates')
                        ->where('vendor_property_id', (int) $propertyRow->id)
                        ->get(['transfer_mode', 'resident_type', 'passenger_type', 'rate', 'base_charge']);

                    if ($transferRows->isNotEmpty()) {
                        $transferOptions = [];
                        $transferRates = [];
                        $transferRateMatrix = [];
                        $transferBaseLocal = 0.0;
                        $transferBaseForeign = 0.0;

                        foreach ($transferRows as $transferRow) {
                            $mode = strtolower(trim((string) ($transferRow->transfer_mode ?? '')));
                            $residentType = strtolower(trim((string) ($transferRow->resident_type ?? '')));
                            $passengerType = strtolower(trim((string) ($transferRow->passenger_type ?? '')));
                            $rate = is_numeric($transferRow->rate ?? null) ? (float) $transferRow->rate : 0.0;
                            $baseCharge = is_numeric($transferRow->base_charge ?? null) ? (float) $transferRow->base_charge : 0.0;

                            if ($mode === '') {
                                continue;
                            }

                            $transferOptions[$mode] = true;
                            if (!isset($transferRateMatrix[$mode])) {
                                $transferRateMatrix[$mode] = [
                                    'local_adult_charge' => 0.0,
                                    'local_child_charge' => 0.0,
                                    'foreign_adult_charge' => 0.0,
                                    'foreign_child_charge' => 0.0,
                                ];
                            }

                            if ($residentType === 'local' && $passengerType === 'adult') {
                                $transferRateMatrix[$mode]['local_adult_charge'] = max(0, $rate);
                            } elseif ($residentType === 'local' && $passengerType === 'child') {
                                $transferRateMatrix[$mode]['local_child_charge'] = max(0, $rate);
                            } elseif ($residentType === 'foreigner' && $passengerType === 'adult') {
                                $transferRateMatrix[$mode]['foreign_adult_charge'] = max(0, $rate);
                                $transferRates[$mode] = max(0, $rate);
                            } elseif ($residentType === 'foreigner' && $passengerType === 'child') {
                                $transferRateMatrix[$mode]['foreign_child_charge'] = max(0, $rate);
                            }

                            if ($residentType === 'local') {
                                $transferBaseLocal = max($transferBaseLocal, max(0, $baseCharge));
                            } elseif ($residentType === 'foreigner') {
                                $transferBaseForeign = max($transferBaseForeign, max(0, $baseCharge));
                            }
                        }

                        $cachedDetails['transfer_options'] = array_values(array_keys($transferOptions));
                        $cachedDetails['transfer_rates'] = $transferRates;
                        $cachedDetails['transfer_rate_matrix'] = $transferRateMatrix;
                        $cachedDetails['transfer_base_local'] = $transferBaseLocal;
                        $cachedDetails['transfer_base_foreign'] = $transferBaseForeign;
                    }
                }

                if (Schema::hasTable('vendor_accommodation_features')) {
                    $featureRows = DB::table('vendor_accommodation_features')
                        ->where('vendor_property_id', (int) $propertyRow->id)
                        ->get(['feature_type', 'feature_key']);

                    if ($featureRows->isNotEmpty()) {
                        $amenities = [];
                        $facilities = [];
                        foreach ($featureRows as $featureRow) {
                            $featureType = strtolower(trim((string) ($featureRow->feature_type ?? '')));
                            $featureKey = trim((string) ($featureRow->feature_key ?? ''));
                            if ($featureKey === '') {
                                continue;
                            }

                            if ($featureType === 'amenity') {
                                $amenities[] = $featureKey;
                            } elseif ($featureType === 'facility') {
                                $facilities[] = $featureKey;
                            }
                        }

                        $cachedDetails['property_amenities'] = array_values(array_unique($amenities));
                        $cachedDetails['property_features'] = array_values(array_unique($facilities));
                    }
                }

                if (Schema::hasTable('vendor_accommodation_policies')) {
                    $policyRow = DB::table('vendor_accommodation_policies')
                        ->where('vendor_property_id', (int) $propertyRow->id)
                        ->first();

                    if ($policyRow) {
                        $cachedDetails['check_in_time'] = trim((string) ($policyRow->check_in_time ?? ($cachedDetails['check_in_time'] ?? '')));
                        $cachedDetails['check_out_time'] = trim((string) ($policyRow->check_out_time ?? ($cachedDetails['check_out_time'] ?? '')));
                        $cachedDetails['check_in_grace_minutes'] = is_numeric($policyRow->check_in_grace_minutes ?? null) ? (int) $policyRow->check_in_grace_minutes : ($cachedDetails['check_in_grace_minutes'] ?? null);
                        $cachedDetails['early_check_in_allowed'] = trim((string) ($policyRow->early_check_in_allowed ?? ($cachedDetails['early_check_in_allowed'] ?? '')));
                        $cachedDetails['late_check_out_allowed'] = trim((string) ($policyRow->late_check_out_allowed ?? ($cachedDetails['late_check_out_allowed'] ?? '')));
                        $cachedDetails['minimum_nights'] = is_numeric($policyRow->minimum_nights ?? null) ? (int) $policyRow->minimum_nights : ($cachedDetails['minimum_nights'] ?? null);
                        $cachedDetails['house_rules'] = trim((string) ($policyRow->house_rules ?? ($cachedDetails['house_rules'] ?? '')));
                        $cachedDetails['child_policy'] = trim((string) ($policyRow->child_policy ?? ($cachedDetails['child_policy'] ?? '')));
                        $cachedDetails['cancellation_policy'] = trim((string) ($policyRow->cancellation_policy ?? ($cachedDetails['cancellation_policy'] ?? '')));
                        $cachedDetails['early_check_in_fee'] = is_numeric($policyRow->early_check_in_fee ?? null) ? (float) $policyRow->early_check_in_fee : ($cachedDetails['early_check_in_fee'] ?? null);
                        $cachedDetails['late_check_out_fee'] = is_numeric($policyRow->late_check_out_fee ?? null) ? (float) $policyRow->late_check_out_fee : ($cachedDetails['late_check_out_fee'] ?? null);
                        $cachedDetails['property_type'] = trim((string) ($policyRow->property_type ?? ($cachedDetails['property_type'] ?? '')));
                        $cachedDetails['star_rating'] = is_numeric($policyRow->star_rating ?? null) ? (int) $policyRow->star_rating : ($cachedDetails['star_rating'] ?? null);
                    }
                }

                return $cachedDetails;
            }
        );

        $propertyRow->listing_details = json_encode($details);
    }

    $facilityCandidates = [];
    foreach (['facilities', 'amenities', 'accommodation_facilities', 'property_amenities', 'property_features'] as $key) {
        if (!array_key_exists($key, $details)) {
            continue;
        }

        $value = $details[$key];
        if (is_array($value)) {
            $facilityCandidates = array_merge($facilityCandidates, $value);
        } elseif (is_string($value)) {
            $facilityCandidates = array_merge($facilityCandidates, preg_split('/[,\n]+/', $value) ?: []);
        }
    }

    $propertyFacilities = collect($facilityCandidates)
        ->map(static fn ($item) => trim((string) $item))
        ->filter(static fn ($item) => $item !== '')
        ->unique()
        ->values();

    // No review/rating columns exist on category tables; ratings come from review tables below.
    $reviewColumn = null;
    $reviewCountColumn = null;

    $guestReviews = collect(Cache::remember(
        'property_profile:guest_reviews:v1:' . (int) $propertyRow->id,
        now()->addMinutes(3),
        static function () use ($propertyRow) {
            $resolvedGuestReviews = collect();
            $reviewTableCandidates = [
                'vendor_property_reviews',
                'property_reviews',
                'customer_reviews',
                'vendor_reviews',
            ];

            foreach ($reviewTableCandidates as $reviewTable) {
                if (!Schema::hasTable($reviewTable)) {
                    continue;
                }

                $columns = Schema::getColumnListing($reviewTable);
                $propertyKey = collect(['vendor_property_id', 'property_id', 'listing_id', 'entity_id'])
                    ->first(static fn ($column) => in_array($column, $columns, true));
                $commentKey = collect(['review_comment', 'comment', 'review_text', 'feedback', 'notes'])
                    ->first(static fn ($column) => in_array($column, $columns, true));

                if ($propertyKey === null || $commentKey === null) {
                    continue;
                }

                $ratingKey = collect(['rating', 'rating_value', 'review_score', 'score'])
                    ->first(static fn ($column) => in_array($column, $columns, true));
                $nameKey = collect(['customer_name', 'guest_name', 'reviewer_name', 'name'])
                    ->first(static fn ($column) => in_array($column, $columns, true));
                $dateKey = collect(['created_at', 'reviewed_at', 'submitted_at', 'updated_at'])
                    ->first(static fn ($column) => in_array($column, $columns, true));
                $statusKey = collect(['status', 'review_status'])
                    ->first(static fn ($column) => in_array($column, $columns, true));

                $reviewQuery = DB::table($reviewTable)->where($propertyKey, (int) $propertyRow->id);

                if ($statusKey !== null) {
                    $reviewQuery->whereIn($statusKey, ['approved', 'published', 'active']);
                }

                if ($dateKey !== null) {
                    $reviewQuery->orderByDesc($dateKey);
                } else {
                    $reviewQuery->orderByDesc('id');
                }

                $rows = $reviewQuery->limit(8)->get();
                if ($rows->isEmpty()) {
                    continue;
                }

                $resolvedGuestReviews = $rows
                    ->map(function ($row) use ($commentKey, $ratingKey, $nameKey, $dateKey) {
                        $comment = trim((string) ($row->{$commentKey} ?? ''));
                        if ($comment === '') {
                            return null;
                        }

                        return [
                            'name' => trim((string) ($nameKey ? ($row->{$nameKey} ?? '') : '')),
                            'comment' => $comment,
                            'rating' => $ratingKey ? (float) ($row->{$ratingKey} ?? 0) : 0.0,
                            'date' => $dateKey ? (string) ($row->{$dateKey} ?? '') : '',
                        ];
                    })
                    ->filter()
                    ->values();

                if ($resolvedGuestReviews->isNotEmpty()) {
                    break;
                }
            }

            return $resolvedGuestReviews->all();
        }
    ));

    $locationLine = trim(implode(', ', array_filter([
        trim((string) ($propertyRow->location ?? '')),
        trim((string) ($propertyRow->island ?? '')),
        trim((string) ($propertyRow->atoll ?? '')),
        trim((string) ($propertyRow->city ?? '')),
    ], static fn ($v) => $v !== '')));

    $nearbyProperties = collect();
    $nearbyRadiusKm = (float) $request->query('nearby_radius_km', 25);
    if (!is_finite($nearbyRadiusKm) || $nearbyRadiusKm <= 0) {
        $nearbyRadiusKm = 25.0;
    }
    $nearbyRadiusKm = max(1.0, min(200.0, $nearbyRadiusKm));
    $nearbyUsesCoordinateRadius = false;
    $nearbyProperties = collect(Cache::remember(
        'property_profile:nearby:v1:' . (int) $propertyRow->id,
        now()->addMinutes(3),
        static function () use ($propertyRow) {
            $currentCategory = trim((string) ($propertyRow->listing_category ?? ''));
            $allCategoryListings = \App\Support\VendorPropertyCompatibilityReader::allActiveListings(300);

            $candidateRows = $allCategoryListings
                ->filter(static fn ($row) => $currentCategory === '' || ($row->listing_category ?? '') === $currentCategory)
                ->filter(static fn ($row) => (int) ($row->id ?? 0) !== (int) $propertyRow->id)
                ->values();

            $preparedNearby = $candidateRows->map(static function ($row) {
                return [
                    'id' => (int) ($row->id ?? 0),
                    'name' => trim((string) ($row->name ?? 'Property')),
                    'base_price' => (float) ($row->base_price ?? 0),
                    'currency' => strtoupper(trim((string) ($row->currency ?? 'MVR'))),
                    'city' => trim((string) ($row->city ?? '')),
                    'island' => trim((string) ($row->island ?? '')),
                    'atoll' => trim((string) ($row->atoll ?? '')),
                    'lat' => null,
                    'lng' => null,
                    'distance_km' => null,
                    'url' => '/property/' . (int) ($row->id ?? 0),
                ];
            })->filter(static fn (array $item) => $item['id'] > 0 && $item['name'] !== '')->values();

            $sourceIsland = strtolower(trim((string) ($propertyRow->island ?? '')));
            $sourceCity = strtolower(trim((string) ($propertyRow->city ?? '')));
            $sourceAtoll = strtolower(trim((string) ($propertyRow->atoll ?? '')));

            $nearbyByLocation = $preparedNearby
                ->filter(static function (array $item) use ($sourceIsland, $sourceCity, $sourceAtoll): bool {
                    $itemIsland = strtolower(trim((string) ($item['island'] ?? '')));
                    $itemCity = strtolower(trim((string) ($item['city'] ?? '')));
                    $itemAtoll = strtolower(trim((string) ($item['atoll'] ?? '')));

                    $matchesIsland = $sourceIsland !== '' && $itemIsland !== '' && $sourceIsland === $itemIsland;
                    $matchesCity = $sourceCity !== '' && $itemCity !== '' && $sourceCity === $itemCity;
                    $matchesAtoll = $sourceAtoll !== '' && $itemAtoll !== '' && $sourceAtoll === $itemAtoll;

                    return $matchesIsland || $matchesCity || $matchesAtoll;
                })
                ->take(6)
                ->values();

            $resolvedNearbyProperties = $nearbyByLocation->isNotEmpty() ? $nearbyByLocation : $preparedNearby->take(6)->values();

            $nearbyPropertyIds = $resolvedNearbyProperties->pluck('id')->filter(static fn ($id) => (int) $id > 0)->map(static fn ($id) => (int) $id)->values();
            $nearbyPropertyThumbById = collect();

            if ($nearbyPropertyIds->isNotEmpty() && Schema::hasTable('vendor_listing_media')) {
                $nearbyPropertyThumbById = DB::table('vendor_listing_media')
                    ->where('entity_type', 'property')
                    ->whereIn('entity_id', $nearbyPropertyIds->all())
                    ->orderByDesc('is_primary')
                    ->orderByDesc('created_at')
                    ->get()
                    ->groupBy(static fn ($media) => (int) ($media->entity_id ?? 0))
                    ->map(static function ($items) {
                        $firstMedia = collect($items)->first();
                        if (!$firstMedia) {
                            return null;
                        }

                        $mediaId = (int) ($firstMedia->id ?? 0);
                        return $mediaId > 0 ? ('/media/vendor/' . $mediaId . '/thumb') : null;
                    });
            }

            return $resolvedNearbyProperties
                ->map(static function (array $item) use ($nearbyPropertyThumbById): array {
                    $locationLine = trim(implode(', ', array_filter([
                        trim((string) ($item['island'] ?? '')),
                        trim((string) ($item['city'] ?? '')),
                        trim((string) ($item['atoll'] ?? '')),
                    ], static fn ($value) => $value !== '')));

                    $item['location_line'] = $locationLine !== '' ? ($locationLine . ', Maldives') : 'Maldives';
                    $item['thumbnail_url'] = (string) ($nearbyPropertyThumbById->get((int) ($item['id'] ?? 0)) ?? '');

                    return $item;
                })
                ->values()
                ->all();
        }
    ));

    $todayDate = now()->toDateString();
    $unavailableDates = Cache::remember(
        'property_profile:unavailable_dates:v1:' . (int) ($propertyRow->id ?? 0) . ':' . $todayDate,
        now()->addMinutes(2),
        static function () use ($propertyRow, $todayDate) {
            $resolvedUnavailableDates = [
                'blocked' => [],
                'reserved' => [],
            ];

            if (Schema::hasTable('vendor_availability_slots')) {
                $slotQuery = DB::table('vendor_availability_slots')
                    ->where('vendor_property_id', (int) ($propertyRow->id ?? 0))
                    ->whereDate('slot_date', '>=', $todayDate);

                if (Schema::hasColumn('vendor_availability_slots', 'listing_category')) {
                    $slotQuery->where(function ($query) {
                        $query->where('listing_category', 'accommodation')
                            ->orWhereNull('listing_category')
                            ->orWhere('listing_category', '');
                    });
                }

                $slotRows = $slotQuery->limit(600)->get(['slot_date', 'inventory', 'reserved_count', 'is_closed']);
                foreach ($slotRows as $slotRow) {
                    $slotDate = trim((string) ($slotRow->slot_date ?? ''));
                    if ($slotDate === '') {
                        continue;
                    }

                    if ((bool) ($slotRow->is_closed ?? false)) {
                        $resolvedUnavailableDates['blocked'][$slotDate] = true;
                        continue;
                    }

                    $inventory = max(0, (int) ($slotRow->inventory ?? 0));
                    $reservedCount = max(0, (int) ($slotRow->reserved_count ?? 0));
                    if ($inventory > 0 && $reservedCount >= $inventory) {
                        $resolvedUnavailableDates['reserved'][$slotDate] = true;
                    }
                }
            }

            if (Schema::hasTable('vendor_reservations')) {
                $reservationQuery = DB::table('vendor_reservations')
                    ->where('vendor_property_id', (int) ($propertyRow->id ?? 0))
                    ->whereNotIn('status', ['cancelled', 'rejected', 'expired', 'failed'])
                    ->whereDate('end_at', '>=', $todayDate);

                if (Schema::hasColumn('vendor_reservations', 'listing_category')) {
                    $reservationQuery->where(function ($query) {
                        $query->where('listing_category', 'accommodation')
                            ->orWhereNull('listing_category')
                            ->orWhere('listing_category', '');
                    });
                }

                $reservationRows = $reservationQuery->limit(240)->get(['start_at', 'end_at']);
                foreach ($reservationRows as $reservationRow) {
                    try {
                        $startDay = Carbon::parse((string) ($reservationRow->start_at ?? ''))->startOfDay();
                        $endExclusive = Carbon::parse((string) ($reservationRow->end_at ?? ''))->startOfDay();
                    } catch (\Throwable $ignored) {
                        continue;
                    }

                    if ($endExclusive->lessThanOrEqualTo($startDay)) {
                        $endExclusive = $startDay->copy()->addDay();
                    }

                    foreach (workationDateSeries($startDay, $endExclusive) as $slotDate) {
                        if ($slotDate >= $todayDate) {
                            $resolvedUnavailableDates['reserved'][$slotDate] = true;
                        }
                    }
                }
            }

            $resolvedUnavailableDates['blocked'] = array_values(array_keys($resolvedUnavailableDates['blocked']));
            $resolvedUnavailableDates['reserved'] = array_values(array_keys($resolvedUnavailableDates['reserved']));

            return $resolvedUnavailableDates;
        }
    );

    return view('property-profile', [
        'property' => $propertyRow,
        'propertyMedia' => $propertyMedia,
        'roomMediaByRoom' => $roomMediaByRoom,
        'rooms' => $rooms,
        'propertyFacilities' => $propertyFacilities,
        'locationLine' => $locationLine,
        'ratingValue' => $reviewColumn ? (float) ($propertyRow->{$reviewColumn} ?? 0) : 0,
        'ratingUsers' => $reviewCountColumn ? (int) ($propertyRow->{$reviewCountColumn} ?? 0) : 0,
        'guestReviews' => $guestReviews,
        'mediaUrl' => $mediaUrl,
        'prefill' => [
            'checkin' => trim((string) $request->query('checkin', '')),
            'checkout' => trim((string) $request->query('checkout', '')),
            'rooms' => max(1, (int) $request->query('rooms', 1)),
            'adults' => max(1, (int) $request->query('adults', 2)),
            'children' => max(0, (int) $request->query('children', 0)),
        ],
        'nearbyProperties' => $nearbyProperties,
        'nearbyRadiusKm' => $nearbyRadiusKm,
        'nearbyUsesCoordinateRadius' => $nearbyUsesCoordinateRadius,
        'todayDate' => $todayDate,
        'unavailableDates' => $unavailableDates,
    ]);
});