@php
    $operationsViewMode = in_array((string) ($operationsViewMode ?? 'reservations'), ['reservations', 'availability'], true)
        ? (string) $operationsViewMode
        : 'reservations';
    $reservationScope = strtolower(trim((string) request()->query('scope', 'all')));
    if (!in_array($reservationScope, ['active', 'pending', 'history', 'all'], true)) {
        $reservationScope = 'all';
    }
    $showAvailabilityPanel = $operationsViewMode === 'availability';
    $showReservationsPanel = $operationsViewMode === 'reservations';
    $distributionSummary = is_array($distributionSummary ?? null) ? $distributionSummary : [];
    $distributionEvents = collect($distributionEvents ?? []);
    $vendorOperationalHealth = is_array($vendorOperationalHealth ?? null) ? $vendorOperationalHealth : [];

    $allVendorCategoryKeys = collect($vendorAllowedCategoryKeys ?? $selectedVendorCategories ?? [])
        ->map(static function ($categoryKey) {
            return vendorPortalCanonicalCategory((string) $categoryKey);
        })
        ->filter(static fn ($categoryKey) => is_string($categoryKey) && $categoryKey !== '')
        ->unique()
        ->values()
        ->all();
    $forcedOperationsCategory = vendorPortalCanonicalCategory((string) ($forcedListingCategory ?? ''));
    if (is_string($forcedOperationsCategory) && $forcedOperationsCategory !== '' && in_array($forcedOperationsCategory, $allVendorCategoryKeys, true)) {
        $allVendorCategoryKeys = [$forcedOperationsCategory];
    }
@endphp
<section id="vendorAvailabilitySection" class="card ops-section {{ $showReservationsPanel ? 'ops-section--reservations' : 'ops-section--availability' }}" aria-label="Vendor availability calendar" data-panel-group="{{ $showAvailabilityPanel ? 'availability' : 'reservations' }}">
            <div class="ops-header">
                <p class="ops-title">{{ $showAvailabilityPanel ? 'Availability Operations' : 'Reservation Operations' }}</p>
                <span class="ops-chip">{{ count($allVendorCategoryKeys ?? []) }} categories</span>
            </div>
            @if (empty($allVendorCategoryKeys))
                <p class="wizard-note" style="margin-bottom:10px;">Availability and reservation controls are locked until at least one category is approved by admin.</p>
            @endif
            @if ($showReservationsPanel)
            <div class="panel-links" aria-label="Category operations actions">
                @if ($showReservationsPanel)
                    <a href="{{ '/vendor/reservations' . ($forcedListingCategory !== '' ? ('?category=' . urlencode((string) $forcedListingCategory) . '&scope=active') : '?scope=active') }}">Active</a>
                    <a href="{{ '/vendor/reservations' . ($forcedListingCategory !== '' ? ('?category=' . urlencode((string) $forcedListingCategory) . '&scope=pending') : '?scope=pending') }}">Pending</a>
                    <a href="{{ '/vendor/reservations' . ($forcedListingCategory !== '' ? ('?category=' . urlencode((string) $forcedListingCategory) . '&scope=history') : '?scope=history') }}">History</a>
                    <a href="{{ '/vendor/reservations' . ($forcedListingCategory !== '' ? ('?category=' . urlencode((string) $forcedListingCategory) . '&scope=all') : '?scope=all') }}">All</a>
                @endif
            </div>
            @endif
            @if ($showReservationsPanel)
                <div class="reservation-command-bar" aria-label="Reservation quick actions">
                    <a class="reservation-command" href="{{ '/vendor/reservations' . ($forcedListingCategory !== '' ? ('?category=' . urlencode((string) $forcedListingCategory) . '&scope=active') : '?scope=active') }}">Open Active Queue</a>
                    <a class="reservation-command" href="{{ '/vendor/reservations' . ($forcedListingCategory !== '' ? ('?category=' . urlencode((string) $forcedListingCategory) . '&scope=pending') : '?scope=pending') }}">Open Pending Queue</a>
                    <a class="reservation-command" href="{{ '/vendor/reservations' . ($forcedListingCategory !== '' ? ('?category=' . urlencode((string) $forcedListingCategory) . '&scope=history') : '?scope=history') }}">View History</a>
                    <a class="reservation-command" href="{{ '/vendor/availability' . ($forcedListingCategory !== '' ? ('?category=' . urlencode((string) $forcedListingCategory)) : '') }}">Go To Calendar</a>
                </div>
            @endif
            @php
                $propertyById = $vendorProperties->keyBy(static fn ($property) => (int) ($property->id ?? 0));
                $serviceById = $vendorServices->keyBy(static fn ($service) => (int) ($service->id ?? 0));
                $roomById = $vendorRooms->keyBy(static fn ($room) => (int) ($room->id ?? 0));
                $labelForCategory = static function (?string $category) use ($vendorCategoryMap): string {
                    $normalized = strtolower(trim((string) $category));
                    if ($normalized === '') {
                        return 'Unassigned';
                    }
                    return (string) ($vendorCategoryMap[$normalized] ?? ucfirst(str_replace('_', ' ', $normalized)));
                };
                $numberFromRecord = static function ($record, array $keys, int $fallback = 0): int {
                    foreach ($keys as $key) {
                        if (is_array($record) && array_key_exists($key, $record) && is_numeric($record[$key])) {
                            return max(0, (int) $record[$key]);
                        }
                        if (is_object($record) && isset($record->{$key}) && is_numeric($record->{$key})) {
                            return max(0, (int) $record->{$key});
                        }
                    }

                    return max(0, $fallback);
                };
                $textFromRecord = static function ($record, array $keys, string $fallback = ''): string {
                    foreach ($keys as $key) {
                        if (is_array($record) && array_key_exists($key, $record)) {
                            $value = trim((string) ($record[$key] ?? ''));
                            if ($value !== '') {
                                return $value;
                            }
                        }
                        if (is_object($record) && isset($record->{$key})) {
                            $value = trim((string) ($record->{$key} ?? ''));
                            if ($value !== '') {
                                return $value;
                            }
                        }
                    }

                    return $fallback;
                };
                $mediaUrlFromRecord = static function ($media, string $variant = 'thumb'): string {
                    if (!$media) {
                        return '';
                    }

                    $mediaId = (int) ($media->id ?? 0);
                    if ($mediaId > 0) {
                        return '/media/vendor/' . $mediaId . '/' . $variant;
                    }

                    foreach (['public_url', 'url', 'media_url', 'file_url'] as $urlKey) {
                        $candidate = trim((string) ($media->{$urlKey} ?? ''));
                        if ($candidate !== '') {
                            return str_starts_with($candidate, 'http://')
                                ? ('https://' . ltrim(substr($candidate, 7), '/'))
                                : $candidate;
                        }
                    }

                    foreach (['path', 'storage_path', 'file_path', 'relative_path'] as $pathKey) {
                        $rawPath = trim((string) ($media->{$pathKey} ?? ''));
                        if ($rawPath === '') {
                            continue;
                        }

                        $resolved = function_exists('portalManagedMediaUrlFromPath')
                            ? portalManagedMediaUrlFromPath($rawPath)
                            : null;

                        if (($resolved === null || trim((string) $resolved) === '') && function_exists('vendorMediaStorageUrlFromPath')) {
                            $resolved = vendorMediaStorageUrlFromPath($rawPath);
                        }

                        if (is_string($resolved) && trim($resolved) !== '') {
                            return trim($resolved);
                        }
                    }

                    return '';
                };
                $pickPrimaryMediaRecord = static function ($items) {
                    return collect($items)
                        ->sortByDesc(static fn ($media): int => (int) ($media->is_primary ?? 0))
                        ->sortByDesc(static fn ($media): int => strtotime((string) ($media->created_at ?? '')) ?: 0)
                        ->first();
                };
                $serviceMediaByServiceId = collect($vendorMediaAssets ?? collect())
                    ->filter(static fn ($media): bool => in_array(strtolower(trim((string) ($media->entity_type ?? ''))), ['service', 'equipment', 'rental_item'], true))
                    ->groupBy(static fn ($media): int => (int) ($media->entity_id ?? 0));

                $availabilityTargetsByCategory = [];
                $availabilityRowsByCategory = [];
                $listingCountByCategory = [];
                $transferRatePropertyTargetsByCategory = [];
                $transferOptionLabels = [
                    'car' => 'Car',
                    'van' => 'Van',
                    'ferry' => 'Ferry',
                    'speedboat' => 'SpeedBoat',
                    'seaplane' => 'SeaPlane',
                    'domestic_flight' => 'Domestic Flight',
                ];
                foreach ($allVendorCategoryKeys as $categoryKey) {
                    $availabilityTargetsByCategory[$categoryKey] = collect();
                    $availabilityRowsByCategory[$categoryKey] = collect();
                    $listingCountByCategory[$categoryKey] = 0;
                    $transferRatePropertyTargetsByCategory[$categoryKey] = collect();
                }

                foreach ($vendorProperties as $property) {
                    $propertyId = (int) ($property->id ?? 0);
                    if ($propertyId <= 0) {
                        continue;
                    }
                    $categoryKey = vendorPortalCanonicalCategory((string) ($property->listing_category ?? ''));
                    if (!is_string($categoryKey) || $categoryKey === '' || !in_array($categoryKey, $allVendorCategoryKeys, true)) {
                        continue;
                    }

                    if (in_array($categoryKey, ['accommodation', 'remote_workspace'], true)) {
                        $propertyDetails = [];
                        if (isset($property->listing_details) && is_string($property->listing_details) && trim((string) $property->listing_details) !== '') {
                            $decodedPropertyDetails = json_decode((string) $property->listing_details, true);
                            if (is_array($decodedPropertyDetails)) {
                                $propertyDetails = $decodedPropertyDetails;
                            }
                        }

                        $configuredTransferOptions = collect(is_array($propertyDetails['transfer_options'] ?? null) ? $propertyDetails['transfer_options'] : [])
                            ->map(static fn ($value): string => strtolower(trim((string) $value)))
                            ->filter(static fn (string $value): bool => $value !== '')
                            ->unique()
                            ->values()
                            ->all();

                        $configuredTransferRates = is_array($propertyDetails['transfer_rates'] ?? null)
                            ? $propertyDetails['transfer_rates']
                            : [];

                        $transferRatePropertyTargetsByCategory[$categoryKey]->push([
                            'id' => $propertyId,
                            'label' => 'Property #' . $propertyId . ' - ' . (string) ($property->name ?? ('Property ' . $propertyId)),
                            'transfer_options' => $configuredTransferOptions,
                            'transfer_rates' => $configuredTransferRates,
                        ]);
                    }

                    // Count properties as listings in summary chips, including accommodation.
                    $listingCountByCategory[$categoryKey]++;

                    // Accommodation availability is managed at room level only.
                    if ($categoryKey === 'accommodation') {
                        continue;
                    }

                    // For sea transport, decode route_schedules and total_seats for the seat-blocking UI.
                    $seaRouteSchedules = [];
                    $seaTotalSeats = 0;
                    if ($categoryKey === 'sea_transport') {
                        $seaDetailsRaw = isset($property->listing_details) && is_string($property->listing_details)
                            ? json_decode((string) $property->listing_details, true)
                            : null;
                        if (is_array($seaDetailsRaw)) {
                            $seaRouteSchedules = is_array($seaDetailsRaw['route_schedules'] ?? null) ? $seaDetailsRaw['route_schedules'] : [];
                            $seaTotalSeats = (int) ($seaDetailsRaw['total_seats'] ?? 0);
                        }
                    }

                    $availabilityTargetsByCategory[$categoryKey]->push([
                        'kind' => 'property',
                        'id' => $propertyId,
                        'property_id' => $propertyId,
                        'service_id' => '',
                        'room_id' => '',
                        'route_name' => '',
                        'label' => 'Property #' . $propertyId . ' - ' . (string) ($property->name ?? ('Property ' . $propertyId)),
                        'route_schedules' => $seaRouteSchedules,
                        'total_seats' => $seaTotalSeats,
                    ]);
                }

                foreach ($vendorServices as $service) {
                    $serviceId = (int) ($service->id ?? 0);
                    if ($serviceId <= 0) {
                        continue;
                    }
                    $categoryKey = vendorPortalCanonicalCategory((string) ($service->listing_category ?? ''));
                    if (!is_string($categoryKey) || $categoryKey === '' || !in_array($categoryKey, $allVendorCategoryKeys, true)) {
                        continue;
                    }
                    $listingCountByCategory[$categoryKey]++;
                    $availabilityTargetsByCategory[$categoryKey]->push([
                        'kind' => 'service',
                        'id' => $serviceId,
                        'property_id' => '',
                        'service_id' => $serviceId,
                        'room_id' => '',
                        'route_name' => '',
                        'label' => 'Service #' . $serviceId . ' - ' . (string) ($service->name ?? ('Service ' . $serviceId)),
                    ]);
                }

                foreach ($vendorRooms as $room) {
                    $roomId = (int) ($room->id ?? 0);
                    if ($roomId <= 0) {
                        continue;
                    }
                    $roomPropertyId = (int) ($room->vendor_property_id ?? 0);
                    $roomProperty = $propertyById->get($roomPropertyId);
                    $roomPropertyName = $roomProperty instanceof \stdClass
                        ? trim((string) ($roomProperty->name ?? ('Property ' . $roomPropertyId)))
                        : ('Property ' . $roomPropertyId);
                    $roomName = trim((string) ($room->name ?? ('Room ' . $roomId)));
                    $categoryKey = 'accommodation';
                    if ($roomProperty instanceof \stdClass) {
                        $categoryFromProperty = vendorPortalCanonicalCategory((string) ($roomProperty->listing_category ?? ''));
                        if (is_string($categoryFromProperty) && $categoryFromProperty !== '') {
                            $categoryKey = $categoryFromProperty;
                        }
                    }
                    if (!in_array($categoryKey, $allVendorCategoryKeys, true)) {
                        if (in_array('accommodation', $allVendorCategoryKeys, true)) {
                            $categoryKey = 'accommodation';
                        } else {
                            continue;
                        }
                    }
                    $listingCountByCategory[$categoryKey]++;
                    $availabilityTargetsByCategory[$categoryKey]->push([
                        'kind' => 'room',
                        'id' => $roomId,
                        'property_id' => $roomPropertyId > 0 ? $roomPropertyId : '',
                        'service_id' => '',
                        'room_id' => $roomId,
                        'property_name' => $roomPropertyName,
                        'room_name' => $roomName,
                        'route_name' => '',
                        'label' => $roomPropertyName . ' -> ' . $roomName,
                    ]);
                }

                foreach ($allVendorCategoryKeys as $categoryKey) {
                    $availabilityTargetsByCategory[$categoryKey] = $availabilityTargetsByCategory[$categoryKey]->sortBy('label')->values();
                }

                foreach ($vendorAvailability as $slot) {
                    $slotNotesRaw = (string) ($slot->notes ?? '');
                    $slotDecodedNotes = is_string($slotNotesRaw) ? json_decode($slotNotesRaw, true) : null;
                    $slotNotesMeta = is_array($slotDecodedNotes) ? $slotDecodedNotes : [];
                    $slotPropertyId = (int) ($slot->vendor_property_id ?? ($slotNotesMeta['vendor_property_id'] ?? 0));
                    $slotServiceId = (int) ($slot->vendor_service_id ?? ($slotNotesMeta['vendor_service_id'] ?? 0));
                    $slotRoomId = (int) (($slot->vendor_room_category_id ?? null) ?? ($slotNotesMeta['vendor_room_category_id'] ?? 0));
                    $slotCategory = vendorPortalCanonicalCategory((string) ($slot->listing_category ?? ($slotNotesMeta['listing_category'] ?? '')));

                    if ($slotCategory === null && $slotRoomId > 0) {
                        $slotCategory = 'accommodation';
                    }
                    if ($slotCategory === null && $slotPropertyId > 0) {
                        $slotProperty = $propertyById->get($slotPropertyId);
                        if ($slotProperty instanceof \stdClass) {
                            $slotCategory = vendorPortalCanonicalCategory((string) ($slotProperty->listing_category ?? ''));
                        }
                    }
                    if ($slotCategory === null && $slotServiceId > 0) {
                        $slotService = $serviceById->get($slotServiceId);
                        if ($slotService instanceof \stdClass) {
                            $slotCategory = vendorPortalCanonicalCategory((string) ($slotService->listing_category ?? ''));
                        }
                    }
                    if (!is_string($slotCategory) || $slotCategory === '' || !in_array($slotCategory, $allVendorCategoryKeys, true)) {
                        continue;
                    }

                    // For accommodation, only room-level availability rows are valid.
                    if ($slotCategory === 'accommodation' && $slotRoomId <= 0) {
                        continue;
                    }

                    $slotTargetLabel = 'Global / Generic';
                    $slotTargetValue = '';
                    if ($slotRoomId > 0) {
                        $slotRoom = $roomById->get($slotRoomId);
                        $slotTargetLabel = $slotRoom instanceof \stdClass
                            ? ('Room #' . $slotRoomId . ' - ' . (string) ($slotRoom->name ?? ('Room ' . $slotRoomId)))
                            : ('Room #' . $slotRoomId);
                        $slotTargetValue = 'room:' . $slotRoomId;
                    } elseif ($slotServiceId > 0) {
                        $slotService = $serviceById->get($slotServiceId);
                        $slotTargetLabel = $slotService instanceof \stdClass
                            ? ('Service #' . $slotServiceId . ' - ' . (string) ($slotService->name ?? ('Service ' . $slotServiceId)))
                            : ('Service #' . $slotServiceId);
                        $slotTargetValue = 'service:' . $slotServiceId;
                    } elseif ($slotPropertyId > 0) {
                        $slotProperty = $propertyById->get($slotPropertyId);
                        $slotTargetLabel = $slotProperty instanceof \stdClass
                            ? ('Property #' . $slotPropertyId . ' - ' . (string) ($slotProperty->name ?? ('Property ' . $slotPropertyId)))
                            : ('Property #' . $slotPropertyId);
                        $slotTargetValue = 'property:' . $slotPropertyId;
                    }

                    $availabilityRowsByCategory[$slotCategory]->push([
                        'slot_date' => (string) ($slot->slot_date ?? ''),
                        'target_label' => $slotTargetLabel,
                        'target_value' => $slotTargetValue,
                        'route_name' => (string) ($slot->route_name ?? ($slotNotesMeta['route_name'] ?? '')),
                        'service_id' => $slotServiceId,
                        'inventory' => (int) ($slot->inventory ?? 0),
                        'reserved_count' => (int) ($slot->reserved_count ?? 0),
                        'is_closed' => (bool) ($slot->is_closed ?? false),
                    ]);
                }

                $reservationRowsByCategory = [];
                $reservationDailyCountsByCategory = [];
                foreach ($allVendorCategoryKeys as $categoryKey) {
                    $reservationRowsByCategory[$categoryKey] = collect();
                    $reservationDailyCountsByCategory[$categoryKey] = [];
                }

                foreach ($vendorReservations as $reservation) {
                    $reservationBreakdown = [];
                    if (isset($reservation->tax_breakdown_json) && is_string($reservation->tax_breakdown_json) && trim((string) $reservation->tax_breakdown_json) !== '') {
                        $decodedReservationBreakdown = json_decode((string) $reservation->tax_breakdown_json, true);
                        if (is_array($decodedReservationBreakdown)) {
                            $reservationBreakdown = $decodedReservationBreakdown;
                        }
                    }
                    $roomPricingBreakdown = is_array($reservationBreakdown['room_pricing'] ?? null) ? $reservationBreakdown['room_pricing'] : null;

                    $reservationNotes = [];
                    if (isset($reservation->notes) && is_string($reservation->notes) && trim((string) $reservation->notes) !== '') {
                        $decodedReservationNotes = json_decode((string) $reservation->notes, true);
                        if (is_array($decodedReservationNotes)) {
                            $reservationNotes = $decodedReservationNotes;
                        }
                    }

                    $reservationPropertyId = (int) ($reservation->vendor_property_id ?? 0);
                    $reservationServiceId = (int) ($reservation->vendor_service_id ?? 0);
                    $reservationRoomId = (int) (($reservation->vendor_room_category_id ?? 0) ?: ($reservationNotes['room_id'] ?? 0));
                    $reservationCategory = vendorPortalCanonicalCategory((string) ($reservation->listing_category ?? ($reservationNotes['category_key'] ?? $reservationNotes['listing_category'] ?? '')));

                    if ($reservationCategory === null && $reservationRoomId > 0) {
                        $reservationCategory = 'accommodation';
                    }
                    if ($reservationCategory === null && $reservationPropertyId > 0) {
                        $reservationProperty = $propertyById->get($reservationPropertyId);
                        if ($reservationProperty instanceof \stdClass) {
                            $reservationCategory = vendorPortalCanonicalCategory((string) ($reservationProperty->listing_category ?? ''));
                        }
                    }
                    if ($reservationCategory === null && $reservationServiceId > 0) {
                        $reservationService = $serviceById->get($reservationServiceId);
                        if ($reservationService instanceof \stdClass) {
                            $reservationCategory = vendorPortalCanonicalCategory((string) ($reservationService->listing_category ?? ''));
                        }
                    }
                    if (!is_string($reservationCategory) || $reservationCategory === '' || !in_array($reservationCategory, $allVendorCategoryKeys, true)) {
                        continue;
                    }

                    // Accommodation bookings can be room-level or property-level depending on creation flow.
                    // Keep both visible so cancellation/refund timelines are never hidden from vendors.

                    $reservationTargetLabel = 'Global / Unlinked';
                    $reservationTargetValue = '';
                    if ($reservationRoomId > 0) {
                        $roomItem = $roomById->get($reservationRoomId);
                        $reservationTargetLabel = $roomItem instanceof \stdClass
                            ? ('Room #' . $reservationRoomId . ' - ' . (string) ($roomItem->name ?? ('Room ' . $reservationRoomId)))
                            : ('Room #' . $reservationRoomId);
                        $reservationTargetValue = 'room:' . $reservationRoomId;
                    } elseif ($reservationServiceId > 0) {
                        $serviceItem = $serviceById->get($reservationServiceId);
                        $reservationTargetLabel = $serviceItem instanceof \stdClass
                            ? ('Service #' . $reservationServiceId . ' - ' . (string) ($serviceItem->name ?? ('Service ' . $reservationServiceId)))
                            : ('Service #' . $reservationServiceId);
                        $reservationTargetValue = 'service:' . $reservationServiceId;
                    } elseif ($reservationPropertyId > 0) {
                        $propertyItem = $propertyById->get($reservationPropertyId);
                        $reservationTargetLabel = $propertyItem instanceof \stdClass
                            ? ('Property #' . $reservationPropertyId . ' - ' . (string) ($propertyItem->name ?? ('Property ' . $reservationPropertyId)))
                            : ('Property #' . $reservationPropertyId);
                        $reservationTargetValue = 'property:' . $reservationPropertyId;
                    }

                    $reservationThumbnailUrl = trim((string) ($reservationNotes['thumbnail_url'] ?? $reservationNotes['room_thumbnail_url'] ?? $reservationNotes['service_thumbnail_url'] ?? ''));
                    if ($reservationThumbnailUrl === '' && $reservationRoomId > 0) {
                        $reservationThumbnailUrl = $mediaUrlFromRecord($pickPrimaryMediaRecord($roomMediaByRoomId->get($reservationRoomId, collect())));
                    }
                    if ($reservationThumbnailUrl === '' && $reservationServiceId > 0) {
                        $reservationThumbnailUrl = $mediaUrlFromRecord($pickPrimaryMediaRecord($serviceMediaByServiceId->get($reservationServiceId, collect())));
                    }
                    if ($reservationThumbnailUrl === '' && $reservationPropertyId > 0) {
                        $reservationThumbnailUrl = $mediaUrlFromRecord($pickPrimaryMediaRecord($propertyMediaByPropertyId->get($reservationPropertyId, collect())));
                    }

                    $startDay = null;
                    $endDay = null;
                    try {
                        $startDay = (new \DateTimeImmutable((string) ($reservation->start_at ?? '')))->setTime(0, 0);
                    } catch (\Exception $ignored) {
                        $startDay = null;
                    }
                    try {
                        $endDay = (new \DateTimeImmutable((string) ($reservation->end_at ?? '')))->setTime(0, 0);
                    } catch (\Exception $ignored) {
                        $endDay = null;
                    }

                    if ($startDay instanceof \DateTimeImmutable) {
                        $lastDay = $startDay;
                        if ($endDay instanceof \DateTimeImmutable && $endDay > $startDay) {
                            $lastDay = $endDay->sub(new \DateInterval('P1D'));
                        }

                        $targetKey = $reservationTargetValue !== '' ? $reservationTargetValue : '__generic__';
                        $cursor = $startDay;
                        $guard = 0;
                        while ($cursor <= $lastDay && $guard < 370) {
                            $dateKey = $cursor->format('Y-m-d');
                            if (!isset($reservationDailyCountsByCategory[$reservationCategory][$targetKey])) {
                                $reservationDailyCountsByCategory[$reservationCategory][$targetKey] = [];
                            }
                            $reservationDailyCountsByCategory[$reservationCategory][$targetKey][$dateKey] =
                                (int) ($reservationDailyCountsByCategory[$reservationCategory][$targetKey][$dateKey] ?? 0) + 1;

                            $cursor = $cursor->add(new \DateInterval('P1D'));
                            $guard++;
                        }
                    }

                    $reservationRowsByCategory[$reservationCategory]->push([
                        'guests' => max(1, (int) ($reservation->guests ?? ($reservationNotes['guests'] ?? 1))),
                        'id' => (int) ($reservation->id ?? 0),
                        'reservation_code' => 'RSV-' . str_pad((string) ((int) ($reservation->id ?? 0)), 6, '0', STR_PAD_LEFT),
                        'created_at' => (string) ($reservation->created_at ?? ''),
                        'target_label' => $reservationTargetLabel,
                        'target_value' => $reservationTargetValue,
                        'room_label' => trim((string) ($reservationNotes['room_name'] ?? $reservationTargetLabel)),
                        'thumbnail_url' => $reservationThumbnailUrl,
                        'meal_plan' => (function () use ($reservationNotes, $roomPricingBreakdown): string {
                            $rawMealPlan = trim((string) (
                                $reservationNotes['meal_plan_label']
                                ?? $reservationNotes['meal_plan']
                                ?? ($roomPricingBreakdown['meal_plan_label'] ?? null)
                                ?? ($roomPricingBreakdown['meal_plan'] ?? null)
                                ?? ($roomPricingBreakdown['board_basis'] ?? null)
                                ?? ''
                            ));

                            if ($rawMealPlan === '') {
                                return 'RO';
                            }

                            $normalizedMealPlan = strtolower(str_replace(['-', '_'], ' ', $rawMealPlan));
                            if (in_array($normalizedMealPlan, ['ro', 'room only', 'roomonly'], true)) {
                                return 'RO';
                            }
                            if (in_array($normalizedMealPlan, ['bb', 'bed and breakfast', 'breakfast'], true)) {
                                return 'BB';
                            }
                            if (in_array($normalizedMealPlan, ['hb', 'half board'], true)) {
                                return 'HB';
                            }
                            if (in_array($normalizedMealPlan, ['fb', 'full board'], true)) {
                                return 'FB';
                            }
                            if (in_array($normalizedMealPlan, ['ai', 'all inclusive'], true)) {
                                return 'AI';
                            }

                            return strtoupper($rawMealPlan);
                        })(),
                        'transfer_method' => trim((string) ($reservationNotes['transfer_option_label'] ?? $reservationNotes['transfer_option'] ?? 'Not selected')),
                        'departure_area' => trim((string) ($reservationNotes['departure_area'] ?? '')),
                        'departure_time_booked' => trim((string) ($reservationNotes['departure_time'] ?? '')),
                        'return_slot' => trim((string) ($reservationNotes['return_slot'] ?? '')),
                        'special_request' => trim((string) ($reservationNotes['service_notes'] ?? $reservationNotes['additional_guest_details'] ?? '')),
                        'customer_name' => (string) ($reservation->customer_name ?? ''),
                        'customer_email' => (string) ($reservation->customer_email ?? ''),
                        'primary_nationality' => trim((string) ($reservationNotes['primary_nationality'] ?? 'Unknown')),
                        'adult_guests' => max(1, (int) (($reservationNotes['adults'] ?? null) ?? ($reservation->adult_guests ?? $reservation->guests ?? 1))),
                        'child_guests' => max(0, (int) ($reservation->child_guests ?? ($reservationNotes['children'] ?? 0))),
                        'infant_guests' => max(0, (int) ($reservationNotes['infants'] ?? 0)),
                        'payment_gateway' => (string) ($reservation->payment_gateway ?? ''),
                        'payment_currency' => (string) ($reservation->payment_currency ?? $reservation->currency ?? 'MVR'),
                        'payment_reference' => (string) ($reservation->payment_reference ?? ''),
                        'start_at' => (string) ($reservation->start_at ?? ''),
                        'end_at' => (string) ($reservation->end_at ?? ''),
                        'status' => (string) ($reservation->status ?? 'pending'),
                        'payment_status' => (string) ($reservation->payment_status ?? 'unpaid'),
                        'has_open_dispute' => (bool) ($reservation->has_open_dispute ?? false),
                        'has_refund_case' => (bool) ($reservation->has_refund_case ?? false),
                        'refund_case_ref' => (string) ($reservation->refund_case_ref ?? ''),
                        'refund_status' => (string) ($reservation->refund_status ?? ''),
                        'refund_requested_at' => (string) ($reservation->refund_requested_at ?? ''),
                        'refund_review_started_at' => (string) ($reservation->refund_review_started_at ?? ''),
                        'refund_approved_at' => (string) ($reservation->refund_approved_at ?? ''),
                        'refund_completed_at' => (string) ($reservation->refund_completed_at ?? ''),
                        'refund_rejected_at' => (string) ($reservation->refund_rejected_at ?? ''),
                        'refund_sla_due_at' => (string) ($reservation->refund_sla_due_at ?? ''),
                        'refund_sla_escalated_at' => (string) ($reservation->refund_sla_escalated_at ?? ''),
                        'payout_status' => (string) ($reservation->payout_status ?? ''),
                        'payout_expected_at' => (string) ($reservation->payout_expected_at ?? ''),
                        'payout_processing_at' => (string) ($reservation->payout_processing_at ?? ''),
                        'payout_paid_at' => (string) ($reservation->payout_paid_at ?? ''),
                        'currency' => (string) ($reservation->currency ?? 'MVR'),
                        'paid_amount' => (float) ($reservation->payment_amount ?? $reservation->invoice_total_amount ?? $reservation->total_amount ?? 0),
                        'subtotal_amount' => (float) ($reservation->subtotal_amount ?? $reservation->total_amount ?? 0),
                        'service_charge_total' => (float) ($reservation->service_charge_total ?? 0),
                        'total_tax_amount' => (float) ($reservation->total_tax_amount ?? 0),
                        'invoice_total_amount' => (float) ($reservation->invoice_total_amount ?? $reservation->total_amount ?? 0),
                        'green_tax_total' => (float) ($reservation->green_tax_total ?? 0),
                        'tgst_total' => (float) ($reservation->tgst_total ?? 0),
                        'cgst_total' => (float) ($reservation->cgst_total ?? 0),
                        'room_pricing' => $roomPricingBreakdown,
                        'is_online_gateway' => in_array(strtolower(trim((string) ($reservation->payment_gateway ?? ''))), ['stripe', 'bml', 'mib', 'stripe_mvr', 'stripe_usd', 'bml_mvr', 'bml_usd', 'mib_mvr', 'mib_usd'], true),
                    ]);
                }

                $calendarStateByCategory = [];
                foreach ($allVendorCategoryKeys as $categoryKey) {
                    $calendarStateByCategory[$categoryKey] = [];

                    $categorySlots = $availabilityRowsByCategory[$categoryKey] ?? collect();
                    foreach ($categorySlots as $slotRow) {
                        $dateKey = (string) ($slotRow['slot_date'] ?? '');
                        if ($dateKey === '') {
                            continue;
                        }

                        $targetKey = (string) ($slotRow['target_value'] ?? '');
                        if ($targetKey === '') {
                            $targetKey = '__generic__';
                        }

                        if (!isset($calendarStateByCategory[$categoryKey][$targetKey])) {
                            $calendarStateByCategory[$categoryKey][$targetKey] = [];
                        }

                        $calendarStateByCategory[$categoryKey][$targetKey][$dateKey] = [
                            'inventory' => (int) ($slotRow['inventory'] ?? 0),
                            'reserved' => (int) ($slotRow['reserved_count'] ?? 0),
                            'is_closed' => (bool) ($slotRow['is_closed'] ?? false),
                            'has_booking' => false,
                        ];
                    }

                    $reservationCountsForCategory = $reservationDailyCountsByCategory[$categoryKey] ?? [];
                    foreach ($reservationCountsForCategory as $targetKey => $dateCounts) {
                        if (!isset($calendarStateByCategory[$categoryKey][$targetKey])) {
                            $calendarStateByCategory[$categoryKey][$targetKey] = [];
                        }
                        foreach ($dateCounts as $dateKey => $reservationCount) {
                            $existing = $calendarStateByCategory[$categoryKey][$targetKey][$dateKey] ?? [
                                'inventory' => 0,
                                'reserved' => 0,
                                'is_closed' => false,
                                'has_booking' => false,
                            ];

                            $existing['reserved'] = max((int) ($existing['reserved'] ?? 0), (int) $reservationCount);
                            $existing['has_booking'] = ((int) $reservationCount > 0) || (bool) ($existing['has_booking'] ?? false);
                            $calendarStateByCategory[$categoryKey][$targetKey][$dateKey] = $existing;
                        }
                    }
                }
            @endphp

            @if (!empty($allVendorCategoryKeys))
                <div class="ops-category-filter-strip" aria-label="{{ $showAvailabilityPanel ? 'Calendar' : 'Booking' }} category toggles">
                    <button
                        class="ops-category-filter-btn is-active"
                        type="button"
                        data-vendor-ops-category-filter="all"
                        data-vendor-ops-mode="{{ $showAvailabilityPanel ? 'availability' : 'reservations' }}"
                        aria-pressed="true"
                    >All Categories</button>
                    @foreach ($allVendorCategoryKeys as $categoryKey)
                        <button
                            class="ops-category-filter-btn"
                            type="button"
                            data-vendor-ops-category-filter="{{ $categoryKey }}"
                            data-vendor-ops-mode="{{ $showAvailabilityPanel ? 'availability' : 'reservations' }}"
                            aria-pressed="false"
                        >{{ $labelForCategory($categoryKey) }}</button>
                    @endforeach
                </div>
            @endif

            <div class="ops-grid" style="grid-template-columns:1fr;">
                @foreach ($allVendorCategoryKeys as $categoryKey)
                    @php
                        $categorySlug = str_replace('_', '-', (string) $categoryKey);
                        $categoryTargets = $availabilityTargetsByCategory[$categoryKey] ?? collect();
                        $transportPropertyTargets = collect();
                        if ($categoryKey === 'transport') {
                            $transportPropertyTargets = $categoryTargets
                                ->filter(static fn ($target) => (string) ($target['kind'] ?? '') === 'property')
                                ->values();
                        }
                        $categoryTransferRateTargets = collect();
                        if (in_array($categoryKey, ['accommodation', 'remote_workspace'], true)) {
                            $categoryTransferRateTargets = ($transferRatePropertyTargetsByCategory[$categoryKey] ?? collect())
                                ->sortBy('label')
                                ->values();
                        }
                        $accommodationRoomTargetsByProperty = collect();
                        $accommodationPropertyChips = collect();
                        if ($categoryKey === 'accommodation') {
                            $accommodationRoomTargetsByProperty = $categoryTargets
                                ->filter(static fn ($target) => (string) ($target['kind'] ?? '') === 'room')
                                ->groupBy(static fn ($target) => (string) ($target['property_name'] ?? ('Property #' . (string) ($target['property_id'] ?? ''))))
                                ->sortKeys();
                            $accommodationPropertyChips = $categoryTargets
                                ->filter(static fn ($target) => (string) ($target['kind'] ?? '') === 'room')
                                ->map(static function ($target): array {
                                    $propertyId = (int) ($target['property_id'] ?? 0);
                                    $propertyName = trim((string) ($target['property_name'] ?? ('Property #' . $propertyId)));
                                    return [
                                        'property_id' => $propertyId,
                                        'label' => $propertyName !== '' ? $propertyName : ('Property #' . $propertyId),
                                    ];
                                })
                                ->filter(static fn ($item): bool => (int) ($item['property_id'] ?? 0) > 0)
                                ->unique('property_id')
                                ->values();
                        }
                        $categorySlots = ($availabilityRowsByCategory[$categoryKey] ?? collect())->sortByDesc('slot_date')->values();
                        $categoryReservations = ($reservationRowsByCategory[$categoryKey] ?? collect())->sortByDesc('start_at')->values();
                        $availabilityBookingRuns = $categoryReservations
                            ->filter(static function (array $row): bool {
                                $status = strtolower(trim((string) ($row['status'] ?? 'pending')));
                                $paymentStatus = strtolower(trim((string) ($row['payment_status'] ?? 'unpaid')));
                                return in_array($status, ['confirmed', 'upcoming'], true) && $paymentStatus === 'paid';
                            })
                            ->sortBy('start_at')
                            ->values();
                        $trackedCount = $categorySlots->count();
                        $closedCount = $categorySlots->where('is_closed', true)->count();
                        $openCount = max(0, $trackedCount - $closedCount);
                        $inventoryTotal = (int) $categorySlots->sum('inventory');
                        $reservationPendingCount = $categoryReservations->where('status', 'pending')->count();
                        $reservationConfirmedCount = $categoryReservations->where('status', 'confirmed')->count();
                        $reservationCompletedCount = $categoryReservations->where('status', 'completed')->count();
                        $reservationCancelledCount = $categoryReservations->where('status', 'cancelled')->count();
                        $reservationRevenueTotal = (float) $categoryReservations->sum('invoice_total_amount');
                        $categoryPropertyIds = $categoryTargets
                            ->map(static fn ($target): int => (int) ($target['property_id'] ?? 0))
                            ->filter(static fn (int $id): bool => $id > 0)
                            ->values()
                            ->all();
                        $roomInventoryItems = collect($vendorRooms ?? [])
                            ->filter(function ($room) use ($categoryPropertyIds, $categoryKey): bool {
                                $roomPropertyId = (int) ($room->vendor_property_id ?? 0);
                                if ($roomPropertyId > 0 && in_array($roomPropertyId, $categoryPropertyIds, true)) {
                                    return true;
                                }

                                return $categoryKey === 'accommodation';
                            })
                            ->values();
                        $serviceInventoryItems = collect($vendorServices ?? [])
                            ->filter(static function ($service) use ($categoryKey): bool {
                                $serviceCategory = vendorPortalCanonicalCategory((string) ($service->listing_category ?? ''));

                                return is_string($serviceCategory) && $serviceCategory === $categoryKey;
                            })
                            ->values();
                        $equipmentInventoryItems = collect($vendorRentalItems ?? [])
                            ->filter(function ($equipment) use ($categoryPropertyIds): bool {
                                $equipmentPropertyId = (int) ($equipment->vendor_property_id ?? 0);
                                if ($equipmentPropertyId > 0 && in_array($equipmentPropertyId, $categoryPropertyIds, true)) {
                                    return true;
                                }

                                return false;
                            })
                            ->values();
                        $roomInventoryTotalUnits = $roomInventoryItems->sum(fn ($room) => $numberFromRecord($room, ['inventory', 'available_rooms', 'room_inventory', 'stock', 'quantity', 'units'], 1));
                        $serviceInventoryTotalUnits = $serviceInventoryItems->sum(fn ($service) => $numberFromRecord($service, ['inventory', 'max_capacity', 'capacity', 'stock', 'quantity', 'units'], 1));
                        $equipmentInventoryTotalUnits = $equipmentInventoryItems->sum(fn ($equipment) => $numberFromRecord($equipment, ['available_quantity', 'inventory', 'quantity', 'stock', 'units'], 1));
                        $calendarTodayKey = now()->format('Y-m-d');
                        $calendarCategoryState = $calendarStateByCategory[$categoryKey] ?? [];
                        $assetInventoryRows = collect();

                        foreach ($categoryTargets as $targetOption) {
                            $targetKind = strtolower(trim((string) ($targetOption['kind'] ?? 'listing')));
                            $targetId = (int) ($targetOption['id'] ?? 0);
                            $targetKey = $targetKind . ':' . $targetId;
                            $calendarState = $calendarCategoryState[$targetKey][$calendarTodayKey] ?? null;
                            $inventoryValue = $numberFromRecord($targetOption, ['inventory', 'total_seats', 'capacity', 'stock', 'units'], 1);
                            if (is_array($calendarState)) {
                                $inventoryValue = max($inventoryValue, (int) ($calendarState['inventory'] ?? 0));
                            }
                            $reservedValue = is_array($calendarState) ? max(0, (int) ($calendarState['reserved'] ?? 0)) : 0;
                            $isClosedValue = is_array($calendarState) ? (bool) ($calendarState['is_closed'] ?? false) : false;
                            $availableValue = max(0, $inventoryValue - $reservedValue);
                            $statusLabel = $isClosedValue
                                ? 'Blocked'
                                : (($reservedValue > 0 && $availableValue <= 0) ? 'Booked Out' : 'Open');
                            $assetTypeLabel = $targetKind === 'room'
                                ? 'Room'
                                : ($targetKind === 'service' ? 'Service' : 'Listing');

                            $assetInventoryRows->push([
                                'asset_type' => $assetTypeLabel,
                                'label' => trim((string) ($targetOption['label'] ?? 'Listing')),
                                'inventory' => $inventoryValue,
                                'reserved' => $reservedValue,
                                'available' => $availableValue,
                                'status' => $statusLabel,
                            ]);
                        }

                        foreach ($equipmentInventoryItems as $equipmentItem) {
                            $equipmentLabel = $textFromRecord($equipmentItem, ['item_name', 'name', 'equipment_name', 'title'], 'Equipment');
                            $equipmentTotal = $numberFromRecord($equipmentItem, ['available_quantity', 'inventory', 'quantity', 'stock', 'units'], 1);
                            $assetInventoryRows->push([
                                'asset_type' => 'Equipment',
                                'label' => $equipmentLabel,
                                'inventory' => $equipmentTotal,
                                'reserved' => 0,
                                'available' => $equipmentTotal,
                                'status' => 'Open',
                            ]);
                        }

                        $assetInventoryRows = $assetInventoryRows
                            ->sortBy([
                                ['asset_type', 'asc'],
                                ['label', 'asc'],
                            ])
                            ->values();

                        $availabilityIssuesEvents = $distributionEvents
                            ->filter(static function ($event): bool {
                                $status = strtolower(trim((string) ($event->status ?? 'received')));

                                return in_array($status, ['failed', 'error', 'dead_letter', 'retrying', 'queued'], true);
                            })
                            ->sortByDesc(static fn ($event) => (string) ($event->updated_at ?? $event->created_at ?? ''))
                            ->values();
                        $connectedChannels = (int) ($distributionSummary['connected_channels'] ?? 0);
                        $failedEventsCount = (int) ($distributionSummary['failed_events'] ?? 0);
                        $pendingEventsCount = (int) ($distributionSummary['pending_events'] ?? 0);
                        $syncReliabilityScore = max(40, min(99, 100 - min(55, ($failedEventsCount * 6) + ($pendingEventsCount * 2))));
                        $automationCoverageScore = max(35, min(98, 30 + ((int) $assetInventoryRows->count() * 5) + ($connectedChannels * 8)));
                        $manualTouchPointsReduced = max(10, min(95, (int) round(($automationCoverageScore + $syncReliabilityScore) / 2)));
                        $migrationReasonLine = $connectedChannels > 0
                            ? 'You are already connected to channel infrastructure. Workation centralizes inventory, calendar, and channel responses into one operating window.'
                            : 'Workation removes spreadsheet-driven updates by giving a single calendar, inventory, and channel response window for daily operations.';
                    @endphp
                    <article class="ops-category-card" data-ops-category-section="category-operations-{{ $categoryKey }}" data-ops-category-key="{{ $categoryKey }}">
                        <button
                            class="ops-category-toggle"
                            type="button"
                            data-ops-category-toggle
                            data-ops-group="category_operations"
                            data-ops-target="availability_panel_{{ $categorySlug }}"
                            aria-expanded="false"
                        >
                            <span class="ops-category-toggle-main">
                                <span class="ops-title">{{ $labelForCategory($categoryKey) }}</span>
                                <span class="ops-chip">{{ $listingCountByCategory[$categoryKey] ?? 0 }} listings / {{ $categoryReservations->count() }} reservations</span>
                            </span>
                            <span class="ops-category-toggle-icon" aria-hidden="true">▾</span>
                        </button>
                        <div id="availability_panel_{{ $categorySlug }}" class="ops-category-body" hidden>
                        @if ($showAvailabilityPanel)
                        <p class="ops-subtitle">Listings in {{ $labelForCategory($categoryKey) }} (category-level view)</p>
                        @if ($categoryKey === 'accommodation')
                            <div class="ops-target-quicklist" style="flex-wrap:nowrap;overflow-x:auto;justify-content:flex-start;flex-direction:row;">
                                @forelse ($accommodationPropertyChips as $propertyChip)
                                    <span class="ops-target-quickpick" style="cursor:default;white-space:nowrap;">{{ (string) ($propertyChip['label'] ?? 'Property') }}</span>
                                @empty
                                    <span class="small">No accommodation properties found.</span>
                                @endforelse
                            </div>
                        @else
                            <div class="ops-target-quicklist" style="flex-wrap:nowrap;overflow-x:auto;justify-content:flex-start;flex-direction:row;">
                                @forelse ($categoryTargets as $targetOption)
                                    @php
                                        $targetLabel = trim((string) ($targetOption['property_name'] ?? $targetOption['label'] ?? 'Listing'));
                                    @endphp
                                    <span class="ops-target-quickpick" style="cursor:default;white-space:nowrap;">{{ $targetLabel !== '' ? $targetLabel : 'Listing' }}</span>
                                @empty
                                    <span class="small">No listings yet in this category.</span>
                                @endforelse
                            </div>
                        @endif

                        <section class="migration-value-strip" aria-label="Workation migration value">
                            <div class="migration-value-head">
                                <p class="ops-subtitle" style="margin:0;">Why Existing Providers Move to Workation</p>
                                <span class="ops-chip">Migration value</span>
                            </div>
                            <p class="small" style="margin:6px 0 0;">{{ $migrationReasonLine }}</p>
                            <div class="migration-value-grid">
                                <article class="migration-value-card">
                                    <p class="metric-label">Automation Coverage</p>
                                    <p class="metric-value">{{ $automationCoverageScore }}%</p>
                                    <p class="small">Assets managed in one workspace: {{ $assetInventoryRows->count() }}</p>
                                </article>
                                <article class="migration-value-card">
                                    <p class="metric-label">Sync Reliability</p>
                                    <p class="metric-value">{{ $syncReliabilityScore }}%</p>
                                    <p class="small">Failed: {{ $failedEventsCount }} | Pending: {{ $pendingEventsCount }}</p>
                                </article>
                                <article class="migration-value-card">
                                    <p class="metric-label">Manual Work Reduced</p>
                                    <p class="metric-value">{{ $manualTouchPointsReduced }}%</p>
                                    <p class="small">Calendar + inventory + channel events in one window</p>
                                </article>
                                <article class="migration-value-card">
                                    <p class="metric-label">Channel Reach</p>
                                    <p class="metric-value">{{ $connectedChannels }}</p>
                                    <p class="small">Connected OTA channels ready for centralized operations</p>
                                </article>
                            </div>
                        </section>

                        <div class="availability-engine-shell">
                            <section class="availability-engine-main">
                                <div class="availability-engine-header">
                                    <p class="ops-subtitle" style="margin:0;">One-Window Availability Desk</p>
                                    <span class="ops-chip">Rooms {{ $roomInventoryItems->count() }} | Services {{ $serviceInventoryItems->count() }} | Equipment {{ $equipmentInventoryItems->count() }}</span>
                                </div>
                                <div class="availability-inventory-highlights">
                                    <article class="availability-inventory-card">
                                        <p class="metric-label">Room Inventory</p>
                                        <p class="metric-value">{{ $roomInventoryTotalUnits }}</p>
                                        <p class="small">Across {{ $roomInventoryItems->count() }} room types</p>
                                    </article>
                                    <article class="availability-inventory-card">
                                        <p class="metric-label">Service Inventory</p>
                                        <p class="metric-value">{{ $serviceInventoryTotalUnits }}</p>
                                        <p class="small">Across {{ $serviceInventoryItems->count() }} service items</p>
                                    </article>
                                    <article class="availability-inventory-card">
                                        <p class="metric-label">Equipment Inventory</p>
                                        <p class="metric-value">{{ $equipmentInventoryTotalUnits }}</p>
                                        <p class="small">Across {{ $equipmentInventoryItems->count() }} equipment items</p>
                                    </article>
                                </div>
                                <div class="ops-table-wrap availability-inventory-table-wrap" style="margin-top:10px;">
                                    <table class="ops-table is-compact" aria-label="{{ $labelForCategory($categoryKey) }} inventory highlights table">
                                        <thead>
                                            <tr>
                                                <th>Asset Type</th>
                                                <th>Asset</th>
                                                <th>Inventory</th>
                                                <th>Reserved</th>
                                                <th>Available</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($assetInventoryRows->take(18) as $inventoryRow)
                                                @php
                                                    $inventoryStatus = strtolower(trim((string) ($inventoryRow['status'] ?? 'open')));
                                                    $inventoryStatusClass = in_array($inventoryStatus, ['open', 'available'], true)
                                                        ? 'ok'
                                                        : (in_array($inventoryStatus, ['booked out', 'queued'], true) ? 'warn' : 'err');
                                                @endphp
                                                <tr>
                                                    <td>{{ (string) ($inventoryRow['asset_type'] ?? 'Listing') }}</td>
                                                    <td>{{ (string) ($inventoryRow['label'] ?? 'Listing') }}</td>
                                                    <td>{{ (int) ($inventoryRow['inventory'] ?? 0) }}</td>
                                                    <td>{{ (int) ($inventoryRow['reserved'] ?? 0) }}</td>
                                                    <td>{{ (int) ($inventoryRow['available'] ?? 0) }}</td>
                                                    <td><span class="status-pill {{ $inventoryStatusClass }}">{{ strtoupper((string) ($inventoryRow['status'] ?? 'open')) }}</span></td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="ops-empty">No inventory rows available yet for this category.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                            <aside class="availability-engine-channel-panel" aria-label="Channel manager communications">
                                <div class="availability-engine-header">
                                    <p class="ops-subtitle" style="margin:0;">Channel Communications</p>
                                    <span class="ops-chip">Live sync</span>
                                </div>
                                <div class="availability-channel-kpis">
                                    <article class="availability-channel-kpi">
                                        <p class="metric-label">Connected</p>
                                        <p class="metric-value">{{ (int) ($distributionSummary['connected_channels'] ?? 0) }}</p>
                                    </article>
                                    <article class="availability-channel-kpi">
                                        <p class="metric-label">Action Required</p>
                                        <p class="metric-value">{{ (int) ($distributionSummary['action_required_channels'] ?? 0) }}</p>
                                    </article>
                                    <article class="availability-channel-kpi">
                                        <p class="metric-label">Failed Events</p>
                                        <p class="metric-value">{{ (int) ($distributionSummary['failed_events'] ?? 0) }}</p>
                                    </article>
                                    <article class="availability-channel-kpi">
                                        <p class="metric-label">Pending Events</p>
                                        <p class="metric-value">{{ (int) ($distributionSummary['pending_events'] ?? 0) }}</p>
                                    </article>
                                </div>
                                <p class="small" style="margin:8px 0 0;">Last sync: {{ trim((string) ($distributionSummary['last_sync_at'] ?? '')) !== '' ? (string) ($distributionSummary['last_sync_at'] ?? '') : 'Not synced yet' }}</p>
                                <div class="availability-channel-feed">
                                    @forelse ($availabilityIssuesEvents->take(5) as $event)
                                        @php
                                            $eventStatus = strtolower(trim((string) ($event->status ?? 'queued')));
                                            $eventDirection = strtolower(trim((string) ($event->direction ?? 'inbound')));
                                            $eventId = (int) ($event->id ?? 0);
                                            $eventStatusClass = in_array($eventStatus, ['failed', 'error', 'dead_letter'], true)
                                                ? 'err'
                                                : (in_array($eventStatus, ['retrying', 'queued'], true) ? 'warn' : 'ok');
                                        @endphp
                                        <article class="availability-channel-event">
                                            <div class="availability-channel-event-head">
                                                <span class="small" style="font-weight:700;color:#1f3e59;">{{ strtoupper((string) ($event->channel_code ?? 'OTA')) }} · {{ strtoupper($eventDirection) }}</span>
                                                <span class="status-pill {{ $eventStatusClass }}">{{ strtoupper($eventStatus) }}</span>
                                            </div>
                                            <p class="small" style="margin:4px 0 0;">{{ trim((string) ($event->event_type ?? 'event')) }}</p>
                                            <p class="small" style="margin:4px 0 0;color:#5b6c7e;">Attempts: {{ max(0, (int) ($event->retry_count ?? 0)) }}</p>
                                            @if (trim((string) ($event->error_message ?? '')) !== '')
                                                <p class="small" style="margin:4px 0 0;color:#7a1f1f;">{{ trim((string) ($event->error_message ?? '')) }}</p>
                                            @endif
                                            @if ($eventId > 0)
                                                <div class="availability-channel-event-actions">
                                                    <form method="post" action="{{ url('/vendor/distribution/events/' . $eventId . '/retry') }}" style="margin:0;">
                                                        @csrf
                                                        <button type="submit" class="btn btn-secondary">{{ $eventDirection === 'outbound' ? 'Requeue' : 'Retry' }}</button>
                                                    </form>
                                                    @if ($eventDirection === 'outbound')
                                                        <form method="post" action="{{ url('/vendor/distribution/events/' . $eventId . '/dispatch-now') }}" style="margin:0;">
                                                            @csrf
                                                            <button type="submit" class="btn btn-secondary">Dispatch Now</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @endif
                                        </article>
                                    @empty
                                        <p class="small" style="margin:0;color:#5b6c7e;">No channel communication issues in queue for this vendor.</p>
                                    @endforelse
                                </div>
                            </aside>
                        </div>

                        <div class="billing-ledger-grid" style="margin-bottom:10px;">
                            <article class="billing-ledger-card">
                                <p class="metric-label">Tracked Days</p>
                                <p class="metric-value">{{ $trackedCount }}</p>
                            </article>
                            <article class="billing-ledger-card">
                                <p class="metric-label">Open Days</p>
                                <p class="metric-value">{{ $openCount }}</p>
                            </article>
                            <article class="billing-ledger-card">
                                <p class="metric-label">Closed Days</p>
                                <p class="metric-value">{{ $closedCount }}</p>
                            </article>
                            <article class="billing-ledger-card">
                                <p class="metric-label">Inventory Total</p>
                                <p class="metric-value">{{ $inventoryTotal }}</p>
                            </article>
                        </div>
                        <div class="ops-grid availability-ops-grid">
                            <form class="ops-form" method="POST" action="/portal/vendor/availability/save" data-availability-form="{{ $categoryKey }}">
                                @csrf
                                <script type="application/json" data-availability-calendar-state>
                                    @json($calendarStateByCategory[$categoryKey] ?? [])
                                </script>
                                <input type="hidden" name="listing_category" value="{{ $categoryKey }}">
                                <input type="hidden" name="vendor_property_id" value="" data-availability-role="property">
                                <input type="hidden" name="vendor_service_id" value="" data-availability-role="service">
                                <input type="hidden" name="vendor_room_category_id" value="" data-availability-role="room">
                                <div class="ops-form-grid">
                                    <div class="ops-field ops-field-wide">
                                        <label>Visual Calendar (click dates to block/unblock)</label>
                                        <div class="availability-calendar" data-availability-calendar>
                                            <div class="availability-calendar-toolbar">
                                                <button type="button" class="availability-calendar-nav" data-calendar-nav="prev" aria-label="Previous month">&lt;</button>
                                                <strong data-calendar-month-label>Month</strong>
                                                <button type="button" class="availability-calendar-nav" data-calendar-nav="next" aria-label="Next month">&gt;</button>
                                            </div>
                                            <div class="availability-calendar-legend">
                                                <span class="availability-calendar-pill is-open">Open</span>
                                                <span class="availability-calendar-pill is-blocked">Blocked</span>
                                                <span class="availability-calendar-pill is-booked">Booked</span>
                                                <span class="availability-calendar-pill is-selected">Today</span>
                                            </div>
                                            <div class="availability-calendar-weekdays" aria-hidden="true">
                                                <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
                                            </div>
                                            <div class="availability-calendar-grid" data-calendar-grid></div>
                                            <p class="small" data-calendar-hint>Select listing/room above. Grey dates are already booked and cannot be edited.</p>
                                        </div>
                                    </div>
                                    <div class="ops-field ops-field-wide">
                                        @php
                                            $parentTargets = collect($categoryTargets)
                                                ->map(static function ($targetOption): array {
                                                    $propertyId = trim((string) ($targetOption['property_id'] ?? ''));
                                                    $serviceId = trim((string) ($targetOption['service_id'] ?? ''));
                                                    $roomId = trim((string) ($targetOption['room_id'] ?? ''));

                                                    if ($propertyId !== '') {
                                                        $parentValue = 'property:' . $propertyId;
                                                        $parentLabel = trim((string) ($targetOption['property_name'] ?? ''));
                                                        if ($parentLabel === '') {
                                                            $parentLabel = 'Property #' . $propertyId;
                                                        }

                                                        return ['value' => $parentValue, 'label' => $parentLabel];
                                                    }

                                                    if ($serviceId !== '') {
                                                        return [
                                                            'value' => 'service:' . $serviceId,
                                                            'label' => 'Service #' . $serviceId,
                                                        ];
                                                    }

                                                    $targetKind = trim((string) ($targetOption['kind'] ?? ''));
                                                    $targetId = trim((string) ($targetOption['id'] ?? ''));
                                                    $fallbackValue = $targetKind . ':' . $targetId;

                                                    return [
                                                        'value' => $fallbackValue,
                                                        'label' => trim((string) ($targetOption['label'] ?? $fallbackValue)),
                                                    ];
                                                })
                                                ->filter(static fn ($item): bool => trim((string) ($item['value'] ?? '')) !== '')
                                                ->unique('value')
                                                ->values();
                                        @endphp
                                        <label for="availability_parent_{{ $categorySlug }}">Step 1: Property / Service</label>
                                        <select id="availability_parent_{{ $categorySlug }}" class="ops-select" data-availability-parent>
                                            <option value="">Select parent listing</option>
                                            @foreach ($parentTargets as $parentTarget)
                                                <option value="{{ (string) ($parentTarget['value'] ?? '') }}">{{ (string) ($parentTarget['label'] ?? '') }}</option>
                                            @endforeach
                                        </select>
                                        <p class="small">Choose parent first, then select a specific room/service target below.</p>
                                    </div>
                                    <div class="ops-field ops-field-wide">
                                        <label for="availability_target_{{ $categorySlug }}">Step 2: Room / Service Target</label>
                                        <select id="availability_target_{{ $categorySlug }}" class="ops-select" data-availability-target>
                                            <option value="">Select specific target</option>
                                            @foreach ($categoryTargets as $targetOption)
                                                @php
                                                    $propertyId = trim((string) ($targetOption['property_id'] ?? ''));
                                                    $serviceId = trim((string) ($targetOption['service_id'] ?? ''));
                                                    $targetKind = trim((string) ($targetOption['kind'] ?? ''));
                                                    $targetId = trim((string) ($targetOption['id'] ?? ''));
                                                    $targetValue = $targetKind . ':' . $targetId;

                                                    if ($propertyId !== '') {
                                                        $parentValue = 'property:' . $propertyId;
                                                        $parentLabel = trim((string) ($targetOption['property_name'] ?? ''));
                                                        if ($parentLabel === '') {
                                                            $parentLabel = 'Property #' . $propertyId;
                                                        }
                                                    } elseif ($serviceId !== '') {
                                                        $parentValue = 'service:' . $serviceId;
                                                        $parentLabel = 'Service #' . $serviceId;
                                                    } else {
                                                        $parentValue = $targetValue;
                                                        $parentLabel = trim((string) ($targetOption['label'] ?? $targetValue));
                                                    }
                                                @endphp
                                                <option
                                                    value="{{ $targetValue }}"
                                                    data-parent-value="{{ $parentValue }}"
                                                    data-parent-label="{{ $parentLabel }}"
                                                    data-property-id="{{ (string) ($targetOption['property_id'] ?? '') }}"
                                                    data-service-id="{{ (string) ($targetOption['service_id'] ?? '') }}"
                                                    data-room-id="{{ (string) ($targetOption['room_id'] ?? '') }}"
                                                    data-route-name="{{ (string) ($targetOption['route_name'] ?? '') }}"
                                                >{{ (string) ($targetOption['label'] ?? '') }}</option>
                                            @endforeach
                                        </select>
                                        <p class="small">Booked and today statuses are automatic. Only block/unblock is manual from this panel.</p>
                                    </div>
                                    <div class="ops-field">
                                        <label for="availability_date_{{ $categorySlug }}">Date</label>
                                        <input id="availability_date_{{ $categorySlug }}" name="slot_date" class="ops-input" type="date">
                                    </div>
                                    <div class="ops-field">
                                        <label for="availability_from_{{ $categorySlug }}">Block/Unblock From (optional)</label>
                                        <input id="availability_from_{{ $categorySlug }}" name="apply_range_from" class="ops-input" type="date">
                                    </div>
                                    <div class="ops-field">
                                        <label for="availability_to_{{ $categorySlug }}">Block/Unblock To (optional)</label>
                                        <input id="availability_to_{{ $categorySlug }}" name="apply_range_to" class="ops-input" type="date">
                                    </div>
                                    <div class="ops-field">
                                        <label for="availability_closed_{{ $categorySlug }}">Action</label>
                                        <select id="availability_closed_{{ $categorySlug }}" name="is_closed" class="ops-select">
                                            <option value="1">Block (hide from booking calendar)</option>
                                            <option value="0">Unblock (make bookable)</option>
                                        </select>
                                    </div>
                                    <div class="ops-field ops-field-wide">
                                        <label for="availability_notes_{{ $categorySlug }}">Unavailable Note (optional)</label>
                                        <textarea id="availability_notes_{{ $categorySlug }}" name="notes" class="ops-textarea" maxlength="2000" placeholder="e.g. Sold out, scratched, maintenance, weather disruption"></textarea>
                                    </div>
                                    <input type="hidden" name="schedule_profile" value="daily">
                                    <input type="hidden" name="inventory" value="1" data-availability-inventory>
                                    <input type="hidden" name="route_name" value="" data-availability-role="route">
                                </div>
                                <button class="btn btn-primary" type="submit">Apply Block / Unblock</button>
                                <p class="small availability-inline-note">Transfer and tariff changes are managed by Workation finance configuration and billing controls.</p>
                            </form>

                            @if ($categoryKey === 'sea_transport')
                                @php
                                    $seaVesselTargets = collect($categoryTargets)
                                        ->filter(static fn ($t) => ($t['kind'] ?? '') === 'property' && ($t['property_id'] ?? '') !== '')
                                        ->values()
                                        ->all();
                                @endphp
                                @if (count($seaVesselTargets) > 0)
                                <style>
                                    .sea-seatmap-vessel { background:#f4f8fd; border:1px solid #ccddf5; border-radius:10px; padding:18px 16px; margin-top:4px; }
                                    .sea-seatmap-vessel .seatmap-bow { width:80px; height:32px; background:#ccddf5; border-radius:50% 50% 0 0 / 100% 100% 0 0; margin:0 auto 4px; display:flex; align-items:center; justify-content:center; font-size:10px; color:#4a6fa5; font-weight:600; letter-spacing:.04em; }
                                    .sea-seatmap-vessel .seatmap-hull { background:#fff; border:2px solid #b8d0ed; border-radius:12px; padding:12px 10px; display:inline-flex; flex-direction:column; gap:6px; min-width:160px; }
                                    .sea-seatmap-vessel .seatmap-stern { width:80px; height:20px; background:#ccddf5; border-radius:0 0 50% 50% / 0 0 100% 100%; margin:4px auto 0; }
                                    .sea-seatmap-vessel .seatmap-row { display:flex; align-items:center; gap:5px; }
                                    .sea-seatmap-vessel .seatmap-row-lbl { width:18px; font-size:10px; color:#8fa8c8; text-align:right; flex-shrink:0; }
                                    .sea-seatmap-vessel .seatmap-aisle { width:16px; flex-shrink:0; border-left:1px dashed #ccddf5; border-right:1px dashed #ccddf5; height:36px; }
                                    .sea-seatmap-vessel .sea-seat {
                                        width:36px; height:36px; border-radius:5px 5px 3px 3px;
                                        background:#e6f4ea; border:2px solid #43a047; color:#2e7d32;
                                        font-size:11px; font-weight:700; cursor:pointer; position:relative;
                                        display:flex; align-items:center; justify-content:center;
                                        transition:background .12s, border-color .12s, color .12s;
                                        flex-shrink:0; user-select:none;
                                    }
                                    .sea-seatmap-vessel .sea-seat::before {
                                        content:''; position:absolute; top:-7px; left:5px; right:5px;
                                        height:6px; background:#c8e6c9; border:2px solid #43a047;
                                        border-bottom:none; border-radius:3px 3px 0 0;
                                        transition:background .12s, border-color .12s;
                                    }
                                    .sea-seatmap-vessel .sea-seat.blocked { background:#ffebee; border-color:#e53935; color:#c62828; }
                                    .sea-seatmap-vessel .sea-seat.blocked::before { background:#ffcdd2; border-color:#e53935; }
                                    .sea-seatmap-vessel .sea-seat:hover:not(.blocked) { background:#b9f6ca; border-color:#2e7d32; }
                                    .sea-seatmap-vessel .sea-seat:hover.blocked { background:#ffcdd2; border-color:#b71c1c; }
                                    .sea-seatmap-legend { display:flex; gap:14px; margin-top:12px; font-size:12px; flex-wrap:wrap; }
                                    .sea-seatmap-legend span { display:flex; align-items:center; gap:5px; }
                                    .sea-seatmap-legend i { width:14px; height:14px; border-radius:3px; border:2px solid; display:inline-block; }
                                    .sea-seatmap-legend i.avail { background:#e6f4ea; border-color:#43a047; }
                                    .sea-seatmap-legend i.blkd  { background:#ffebee; border-color:#e53935; }
                                    .sea-seatmap-summary { font-size:13px; font-weight:600; margin-top:10px; padding:7px 12px; border-radius:6px; display:inline-block; }
                                    .sea-seatmap-summary.has-blocked { background:#fff3e0; color:#e65100; }
                                    .sea-seatmap-summary.all-clear   { background:#e8f5e9; color:#2e7d32; }
                                </style>
                                <form class="ops-form" method="POST" action="/portal/vendor/availability/save" id="sea_seat_block_form_{{ $categorySlug }}">
                                    @csrf
                                    <input type="hidden" name="listing_category" value="sea_transport">
                                    <input type="hidden" name="schedule_profile" value="one_off">
                                    <input type="hidden" name="is_closed" value="0">
                                    <input type="hidden" name="vendor_property_id" id="sea_sb_property_{{ $categorySlug }}" value="">
                                    <input type="hidden" name="route_name" id="sea_sb_route_{{ $categorySlug }}" value="">
                                    <input type="hidden" name="inventory" id="sea_sb_inventory_{{ $categorySlug }}" value="0">
                                    <input type="hidden" name="notes" id="sea_sb_notes_hid_{{ $categorySlug }}" value="">

                                    @php
                                        $seaSbJson = collect($seaVesselTargets)->map(static fn ($t) => [
                                            'id'              => (int) ($t['property_id'] ?? 0),
                                            'label'           => (string) ($t['label'] ?? ''),
                                            'total_seats'     => (int) ($t['total_seats'] ?? 0),
                                            'route_schedules' => $t['route_schedules'] ?? [],
                                        ])->values()->all();
                                    @endphp
                                    <script type="application/json" id="sea_sb_data_{{ $categorySlug }}">
                                        @json($seaSbJson)
                                    </script>

                                    <p class="label" style="margin-bottom:0.4rem;">Seat Map — Block Externally Sold Seats</p>
                                    <p class="small" style="margin-bottom:1rem; color:#555;">Select a vessel, route leg and date — then click individual seats to mark them as blocked (sold via OTAs or other channels).</p>

                                    <div class="ops-form-grid">
                                        <div class="ops-field ops-field-wide">
                                            <label for="sea_sb_vessel_{{ $categorySlug }}">Vessel</label>
                                            <select id="sea_sb_vessel_{{ $categorySlug }}" class="ops-select" required>
                                                <option value="">Select vessel</option>
                                                @foreach ($seaVesselTargets as $vt)
                                                    <option value="{{ (int) ($vt['property_id'] ?? 0) }}">{{ (string) ($vt['label'] ?? '') }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="ops-field ops-field-wide">
                                            <label for="sea_sb_leg_{{ $categorySlug }}">Route Leg / Schedule</label>
                                            <select id="sea_sb_leg_{{ $categorySlug }}" class="ops-select" required disabled>
                                                <option value="">Select vessel first</option>
                                            </select>
                                        </div>
                                        <div class="ops-field">
                                            <label for="sea_sb_date_{{ $categorySlug }}">Date</label>
                                            <input id="sea_sb_date_{{ $categorySlug }}" name="slot_date" class="ops-input" type="date" required>
                                        </div>
                                    </div>

                                    <div id="sea_sb_mapwrap_{{ $categorySlug }}" style="display:none; margin-top:14px;">
                                        <p class="small" style="font-weight:600; margin-bottom:8px; color:#334;">Click seats to toggle blocked / available:</p>
                                        <div style="overflow-x:auto;">
                                            <div class="sea-seatmap-vessel" style="display:inline-block;">
                                                <div class="seatmap-bow">BOW</div>
                                                <div class="seatmap-hull" id="sea_sb_seatmap_{{ $categorySlug }}"></div>
                                                <div class="seatmap-stern"></div>
                                            </div>
                                        </div>
                                        <div class="sea-seatmap-legend">
                                            <span><i class="avail"></i> Available</span>
                                            <span><i class="blkd"></i> Blocked (external)</span>
                                        </div>
                                        <p class="sea-seatmap-summary all-clear" id="sea_sb_summary_{{ $categorySlug }}"></p>
                                    </div>

                                    <div style="margin-top:14px;">
                                        <button class="btn btn-primary" type="submit" id="sea_sb_submit_{{ $categorySlug }}" disabled>Apply Seat Blocking</button>
                                    </div>
                                </form>

                                <script>
                                (function () {
                                    const slug = {{ Js::from($categorySlug) }};
                                    const vesselData = JSON.parse(document.getElementById('sea_sb_data_' + slug).textContent);

                                    const vesselSel   = document.getElementById('sea_sb_vessel_' + slug);
                                    const legSel      = document.getElementById('sea_sb_leg_' + slug);
                                    const dateInp     = document.getElementById('sea_sb_date_' + slug);
                                    const mapWrap     = document.getElementById('sea_sb_mapwrap_' + slug);
                                    const seatHull    = document.getElementById('sea_sb_seatmap_' + slug);
                                    const summaryEl   = document.getElementById('sea_sb_summary_' + slug);
                                    const propertyHid = document.getElementById('sea_sb_property_' + slug);
                                    const routeHid    = document.getElementById('sea_sb_route_' + slug);
                                    const inventoryHid = document.getElementById('sea_sb_inventory_' + slug);
                                    const notesHid    = document.getElementById('sea_sb_notes_hid_' + slug);
                                    const submitBtn   = document.getElementById('sea_sb_submit_' + slug);

                                    let totalSeats = 0;
                                    const blocked = new Set();

                                    function fmtLeg(leg) {
                                        const code = leg.route_code || '';
                                        const dep  = leg.dep_time   || '';
                                        const arr  = leg.arr_time   || '';
                                        const days = Array.isArray(leg.days) && leg.days.length ? '  [' + leg.days.join(', ') + ']' : '';
                                        if (code) return code + (dep ? '  ' + dep : '') + (arr ? ' → ' + arr : '') + days;
                                        return (leg.origin || 'Origin') + ' → ' + (leg.destination || 'Dest') + (dep ? '  ' + dep : '') + days;
                                    }

                                    function updateState() {
                                        const remaining = Math.max(0, totalSeats - blocked.size);
                                        inventoryHid.value = remaining;
                                        const seatList = Array.from(blocked).sort(function (a, b) { return a - b; });
                                        notesHid.value = blocked.size > 0
                                            ? 'Externally blocked seats: ' + seatList.join(', ')
                                            : '';
                                        if (blocked.size > 0) {
                                            summaryEl.textContent = blocked.size + ' seat' + (blocked.size !== 1 ? 's' : '') + ' blocked  ·  ' + remaining + ' of ' + totalSeats + ' available';
                                            summaryEl.className = 'sea-seatmap-summary has-blocked';
                                        } else {
                                            summaryEl.textContent = 'All ' + totalSeats + ' seats available — none blocked';
                                            summaryEl.className = 'sea-seatmap-summary all-clear';
                                        }
                                        submitBtn.disabled = legSel.value === '' || dateInp.value === '';
                                    }

                                    function makeSeat(n) {
                                        const btn = document.createElement('button');
                                        btn.type = 'button';
                                        btn.className = 'sea-seat';
                                        btn.dataset.n = n;
                                        btn.textContent = n;
                                        btn.setAttribute('aria-label', 'Seat ' + n);
                                        btn.addEventListener('click', function () {
                                            const num = parseInt(this.dataset.n, 10);
                                            if (blocked.has(num)) {
                                                blocked.delete(num);
                                                this.classList.remove('blocked');
                                            } else {
                                                blocked.add(num);
                                                this.classList.add('blocked');
                                            }
                                            updateState();
                                        });
                                        return btn;
                                    }

                                    function renderMap() {
                                        seatHull.innerHTML = '';
                                        blocked.clear();
                                        if (totalSeats <= 0) {
                                            seatHull.innerHTML = '<p class="small" style="color:#8fa8c8; padding:8px;">Set total seats on the listing form first.</p>';
                                            mapWrap.style.display = 'block';
                                            updateState();
                                            return;
                                        }
                                        // Speedboat/ferry layout: 1+1 per row if ≤10 seats, else 2+2
                                        const left  = totalSeats <= 10 ? 1 : 2;
                                        const right = totalSeats <= 10 ? 1 : 2;
                                        const perRow = left + right;
                                        let n = 1, rowNum = 1;
                                        while (n <= totalSeats) {
                                            const row = document.createElement('div');
                                            row.className = 'seatmap-row';
                                            const lbl = document.createElement('span');
                                            lbl.className = 'seatmap-row-lbl';
                                            lbl.textContent = rowNum;
                                            row.appendChild(lbl);
                                            for (let i = 0; i < left && n <= totalSeats; i++, n++) {
                                                row.appendChild(makeSeat(n));
                                            }
                                            const aisle = document.createElement('span');
                                            aisle.className = 'seatmap-aisle';
                                            row.appendChild(aisle);
                                            for (let i = 0; i < right && n <= totalSeats; i++, n++) {
                                                row.appendChild(makeSeat(n));
                                            }
                                            seatHull.appendChild(row);
                                            rowNum++;
                                        }
                                        mapWrap.style.display = 'block';
                                        updateState();
                                    }

                                    vesselSel.addEventListener('change', function () {
                                        const id = parseInt(vesselSel.value, 10) || 0;
                                        const vessel = vesselData.find(function (v) { return v.id === id; });
                                        legSel.innerHTML = '<option value="">Select route leg</option>';
                                        legSel.disabled = true;
                                        mapWrap.style.display = 'none';
                                        propertyHid.value = id || '';
                                        routeHid.value = '';
                                        submitBtn.disabled = true;
                                        totalSeats = 0;
                                        if (!vessel) return;
                                        totalSeats = vessel.total_seats || 0;
                                        const legs = Array.isArray(vessel.route_schedules) ? vessel.route_schedules : [];
                                        if (legs.length === 0) {
                                            const opt = document.createElement('option');
                                            opt.value = 'general';
                                            opt.textContent = 'General (no specific leg defined)';
                                            legSel.appendChild(opt);
                                        } else {
                                            legs.forEach(function (leg, i) {
                                                const opt = document.createElement('option');
                                                opt.value = leg.route_code || ('leg_' + (i + 1));
                                                opt.textContent = fmtLeg(leg);
                                                legSel.appendChild(opt);
                                            });
                                        }
                                        legSel.disabled = false;
                                    });

                                    legSel.addEventListener('change', function () {
                                        routeHid.value = legSel.value;
                                        if (legSel.value !== '') {
                                            renderMap();
                                        } else {
                                            mapWrap.style.display = 'none';
                                            submitBtn.disabled = true;
                                        }
                                    });

                                    dateInp.addEventListener('change', function () {
                                        if (legSel.value !== '') updateState();
                                    });
                                })();
                                </script>
                                @endif
                            @endif

                            @if ($categoryKey === 'transport')
                                <form class="ops-form" method="POST" action="/portal/vendor/transport/tariff/save">
                                    @csrf
                                    <p class="label">Transport Tariff (Availability + Bookings)</p>
                                    <div class="ops-form-grid">
                                        <div class="ops-field ops-field-wide">
                                            <label for="transport_tariff_property_{{ $categorySlug }}">Vehicle / Vessel Listing</label>
                                            <select id="transport_tariff_property_{{ $categorySlug }}" name="vendor_property_id" class="ops-select" required>
                                                <option value="">Select transport listing</option>
                                                @foreach ($transportPropertyTargets as $transportTarget)
                                                    <option value="{{ (int) ($transportTarget['id'] ?? 0) }}">{{ (string) ($transportTarget['label'] ?? ('Property #' . (int) ($transportTarget['id'] ?? 0))) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="ops-field">
                                            <label for="transport_tariff_mode_{{ $categorySlug }}">Tariff Type</label>
                                            <select id="transport_tariff_mode_{{ $categorySlug }}" name="tariff_mode" class="ops-select" required>
                                                <option value="per_trip">Per Trip</option>
                                                <option value="hourly">Hourly Hire</option>
                                                <option value="daily">Daily Hire</option>
                                                <option value="private_hire">Private Hire</option>
                                            </select>
                                        </div>
                                        <div class="ops-field">
                                            <label for="transport_per_trip_rate_{{ $categorySlug }}">Per Trip Rate (MVR)</label>
                                            <input id="transport_per_trip_rate_{{ $categorySlug }}" name="per_trip_rate" class="ops-input" type="number" min="0" step="0.01" value="0">
                                        </div>
                                        <div class="ops-field">
                                            <label for="transport_hourly_rate_{{ $categorySlug }}">Hourly Hire Rate (MVR)</label>
                                            <input id="transport_hourly_rate_{{ $categorySlug }}" name="hourly_rate" class="ops-input" type="number" min="0" step="0.01" value="0">
                                        </div>
                                        <div class="ops-field">
                                            <label for="transport_daily_rate_{{ $categorySlug }}">Daily Hire Rate (MVR)</label>
                                            <input id="transport_daily_rate_{{ $categorySlug }}" name="daily_rate" class="ops-input" type="number" min="0" step="0.01" value="0">
                                        </div>
                                        <div class="ops-field">
                                            <label for="transport_private_hire_rate_{{ $categorySlug }}">Private Hire Tariff (MVR)</label>
                                            <input id="transport_private_hire_rate_{{ $categorySlug }}" name="private_hire_rate" class="ops-input" type="number" min="0" step="0.01" value="0">
                                        </div>
                                    </div>
                                    <p class="small">Set the tariff mode and rates after creating the vehicle/vessel listing. Customers will see these tariffs during booking.</p>
                                    <button class="btn btn-secondary" type="submit">Save Transport Tariff</button>
                                </form>
                            @endif

                            <div class="ops-table-wrap">
                                <table class="ops-table" aria-label="{{ $labelForCategory($categoryKey) }} booking runs table">
                                    <thead>
                                        <tr>
                                            <th>Check-in</th>
                                            <th>Check-out</th>
                                            <th>Target</th>
                                            <th>Guest</th>
                                            <th>Guests</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($availabilityBookingRuns->take(24) as $runRow)
                                            <tr>
                                                <td>{{ (string) ($runRow['start_at'] ?? '-') }}</td>
                                                <td>{{ (string) ($runRow['end_at'] ?? '-') }}</td>
                                                <td>{{ (string) ($runRow['target_label'] ?? 'N/A') }}</td>
                                                <td>{{ (string) ($runRow['customer_name'] ?? 'Guest') }}</td>
                                                <td>
                                                    {{ max(1, (int) ($runRow['adult_guests'] ?? 1))
                                                        + max(0, (int) ($runRow['child_guests'] ?? 0))
                                                        + max(0, (int) ($runRow['infant_guests'] ?? 0)) }}
                                                </td>
                                                <td>{{ strtoupper((string) ($runRow['status'] ?? 'confirmed')) }} / {{ strtoupper((string) ($runRow['payment_status'] ?? 'paid')) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="ops-empty">No confirmed paid booking runs yet for {{ strtolower($labelForCategory($categoryKey)) }}.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif

                        @php
                            $reservationScopeFiltered = $categoryReservations->filter(static function (array $row) use ($reservationScope): bool {
                                $status = strtolower(trim((string) ($row['status'] ?? 'pending')));
                                $paymentStatus = strtolower(trim((string) ($row['payment_status'] ?? 'unpaid')));

                                return match ($reservationScope) {
                                    'active' => in_array($status, ['confirmed', 'upcoming', 'checked_in', 'checked_out'], true) && $paymentStatus === 'paid',
                                    'pending' => in_array($status, ['pending', 'cancel_requested'], true) || in_array($paymentStatus, ['unpaid', 'partially_paid'], true),
                                    'history' => in_array($status, ['cancel_requested', 'cancelled', 'completed', 'expired', 'failed', 'rejected'], true) || in_array($paymentStatus, ['refunded'], true),
                                    default => true,
                                };
                            })->values();
                        @endphp

                        @if ($showReservationsPanel)
                        <p class="ops-subtitle" style="margin-top:12px;">{{ $labelForCategory($categoryKey) }} Reservations ({{ strtoupper($reservationScope) }})</p>
                        <div class="billing-ledger-grid" style="margin-bottom:10px;">
                            <article class="billing-ledger-card">
                                <p class="metric-label">Pending</p>
                                <p class="metric-value">{{ $reservationScopeFiltered->where('status', 'pending')->count() }}</p>
                            </article>
                            <article class="billing-ledger-card">
                                <p class="metric-label">Confirmed</p>
                                <p class="metric-value">{{ $reservationScopeFiltered->where('status', 'confirmed')->count() }}</p>
                            </article>
                            <article class="billing-ledger-card">
                                <p class="metric-label">In-House</p>
                                <p class="metric-value">{{ $reservationScopeFiltered->where('status', 'checked_in')->count() }}</p>
                            </article>
                            <article class="billing-ledger-card">
                                <p class="metric-label">Checked-Out</p>
                                <p class="metric-value">{{ $reservationScopeFiltered->where('status', 'checked_out')->count() }}</p>
                            </article>
                            <article class="billing-ledger-card">
                                <p class="metric-label">Completed</p>
                                <p class="metric-value">{{ $reservationScopeFiltered->where('status', 'completed')->count() }}</p>
                            </article>
                            <article class="billing-ledger-card">
                                <p class="metric-label">Booked Revenue</p>
                                <p class="metric-value">MVR {{ number_format((float) $reservationScopeFiltered->sum('invoice_total_amount'), 2) }}</p>
                            </article>
                        </div>

                        <div class="vendor-booking-card-list" aria-label="{{ $labelForCategory($categoryKey) }} reservation cards">
                            @forelse ($reservationScopeFiltered->take(30) as $reservationRow)
                                @php
                                    $rsvId = (int) ($reservationRow['id'] ?? 0);
                                    $adults = max(1, (int) ($reservationRow['adult_guests'] ?? 1));
                                    $children = max(0, (int) ($reservationRow['child_guests'] ?? 0));
                                    $infants = max(0, (int) ($reservationRow['infant_guests'] ?? 0));
                                    $bookedGuests = max(1, (int) ($reservationRow['guests'] ?? 1));
                                    $totalGuests = max($bookedGuests, ($adults + $children + $infants));
                                    if (($children + $infants) === 0 && $adults < $totalGuests) {
                                        $adults = $totalGuests;
                                    }
                                    $nights = 0;
                                    try {
                                        $startDate = new \DateTimeImmutable((string) ($reservationRow['start_at'] ?? ''));
                                        $endDate = new \DateTimeImmutable((string) ($reservationRow['end_at'] ?? ''));
                                        $nights = max(0, (int) $startDate->diff($endDate)->days);
                                    } catch (\Exception $ignored) {
                                        $nights = 0;
                                    }
                                    $checkInDateLabel = trim((string) ($reservationRow['start_at'] ?? '')) !== ''
                                        ? substr((string) ($reservationRow['start_at'] ?? ''), 0, 10)
                                        : '-';
                                    $checkOutDateLabel = trim((string) ($reservationRow['end_at'] ?? '')) !== ''
                                        ? substr((string) ($reservationRow['end_at'] ?? ''), 0, 10)
                                        : '-';
                                    $paymentStatusValue = strtolower(trim((string) ($reservationRow['payment_status'] ?? 'unpaid')));
                                    $paymentStatusClass = in_array($paymentStatusValue, ['paid', 'captured', 'settled'], true)
                                        ? 'ok'
                                        : (in_array($paymentStatusValue, ['partially_paid', 'pending'], true) ? 'warn' : 'err');
                                    $bookingStatusValue = strtolower(trim((string) ($reservationRow['status'] ?? 'pending')));
                                    $bookingStatusClass = in_array($bookingStatusValue, ['confirmed', 'checked_in', 'checked_out', 'completed'], true)
                                        ? 'ok'
                                        : (in_array($bookingStatusValue, ['pending', 'cancel_requested'], true) ? 'warn' : 'err');
                                    $rowStatus = strtolower(trim((string) ($reservationRow['status'] ?? 'pending')));
                                    $rowPaymentStatus = strtolower(trim((string) ($reservationRow['payment_status'] ?? 'unpaid')));
                                    $timelineOptions = [
                                        'pending' => 'Booked (Pending Confirmation)',
                                        'cancel_requested' => 'Cancel Requested (Customer)',
                                        'confirmed' => 'Confirmed',
                                        'checked_in' => 'Guest Checked-In',
                                        'checked_out' => 'Guest Checked-Out',
                                        'completed' => 'Stay Completed (Ready for Payout)',
                                        'cancelled' => 'Cancelled',
                                    ];
                                    $canDeleteReservation = in_array($rowStatus, ['cancelled', 'failed', 'expired', 'rejected'], true)
                                        && in_array($rowPaymentStatus, ['unpaid', 'failed', 'cancelled', 'refunded'], true)
                                        && !((bool) ($reservationRow['has_open_dispute'] ?? false))
                                        && !((bool) ($reservationRow['has_refund_case'] ?? false));
                                @endphp
                                <article class="vendor-booking-card">
                                    <div class="vendor-booking-meta-bar">
                                        <span>
                                            Reservation <span class="vendor-booking-ref">{{ (string) ($reservationRow['reservation_code'] ?? ('RSV-' . str_pad((string) (int) ($reservationRow['id'] ?? 0), 6, '0', STR_PAD_LEFT))) }}</span>
                                            &middot; Booked: {{ trim((string) ($reservationRow['created_at'] ?? '')) !== '' ? substr((string) ($reservationRow['created_at'] ?? ''), 0, 10) : 'N/A' }}
                                        </span>
                                        <span class="status-pill {{ $bookingStatusClass }}">{{ strtoupper((string) ($reservationRow['status'] ?? 'pending')) }}</span>
                                    </div>

                                    <div class="vendor-booking-body">
                                        @php
                                            $reservationThumb = trim((string) ($reservationRow['thumbnail_url'] ?? ''));
                                            $reservationThumbFallback = '/images/placeholders/listing-fallback.svg';
                                        @endphp
                                        <div class="vendor-booking-thumb" aria-hidden="true">
                                            @if ($reservationThumb !== '')
                                                <img src="{{ $reservationThumb }}" alt="{{ (string) ($reservationRow['room_label'] ?? $reservationRow['target_label'] ?? 'Reservation') }} thumbnail" loading="lazy" onerror="if(!this.dataset.fb && '{{ $reservationThumbFallback }}' !== '' && this.src !== '{{ $reservationThumbFallback }}' && !this.src.startsWith('data:')){this.dataset.fb='1';this.src='{{ $reservationThumbFallback }}';}else{this.onerror=null;}">
                                            @else
                                                <i class="fa-solid fa-hotel"></i>
                                            @endif
                                        </div>
                                        <div class="vendor-booking-info">
                                            <div class="vendor-booking-title-row">
                                                <span class="vendor-booking-title">{{ (string) ($reservationRow['target_label'] ?? 'Global / Unlinked') }}</span>
                                                <span class="vendor-booking-price">{{ (string) ($reservationRow['currency'] ?? 'MVR') }} {{ number_format((float) ($reservationRow['invoice_total_amount'] ?? 0), 2) }}</span>
                                            </div>
                                            <div class="vendor-booking-line"><strong>Guest:</strong> {{ (string) ($reservationRow['customer_name'] ?? '') }} &middot; {{ (string) ($reservationRow['customer_email'] ?? '') }}</div>
                                            <div class="vendor-booking-line"><strong>Stay:</strong> {{ $checkInDateLabel }} to {{ $checkOutDateLabel }} @if($categoryKey !== 'excursion')&middot; {{ $nights }} night{{ $nights !== 1 ? 's' : '' }}@endif</div>
                                            <div class="vendor-booking-line"><strong>Occupancy:</strong> {{ $totalGuests }} total (A{{ $adults }} / C{{ $children }} / I{{ $infants }})</div>
                                            <div class="vendor-booking-line"><strong>Payment:</strong> <span class="status-pill {{ $paymentStatusClass }}">{{ strtoupper((string) ($reservationRow['payment_status'] ?? 'unpaid')) }}</span></div>
                                            @if (trim((string) ($reservationRow['special_request'] ?? '')) !== '')
                                                <div class="vendor-booking-line"><strong>Special request:</strong> {{ (string) ($reservationRow['special_request'] ?? '') }}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="vendor-booking-actions">
                                        <button type="button" class="btn btn-secondary reservation-row-toggle" data-reservation-row-toggle="rsv-{{ $rsvId }}" aria-expanded="false" aria-controls="rsv-detail-{{ $rsvId }}">Details</button>
                                        <form class="inline-status-form" method="POST" action="/portal/vendor/reservations/{{ (int) ($reservationRow['id'] ?? 0) }}/status">
                                            @csrf
                                            <select class="ops-select" name="status" required>
                                                @foreach ($timelineOptions as $timelineValue => $timelineLabel)
                                                    <option value="{{ $timelineValue }}" @selected($rowStatus === $timelineValue)>{{ $timelineLabel }}</option>
                                                @endforeach
                                            </select>
                                            <textarea name="cancel_reason" class="ops-input" rows="2" maxlength="1000" placeholder="Cancellation reason (required for paid booking cancellation request)">{{ old('cancel_reason', '') }}</textarea>
                                            <button class="btn btn-secondary" type="submit">Save Timeline</button>
                                        </form>
                                        @if ($canDeleteReservation)
                                            <form method="POST" action="/portal/vendor/reservations/{{ (int) ($reservationRow['id'] ?? 0) }}/delete" onsubmit="return confirm('Remove this cancelled booking from your vendor portal list?');" style="margin-top:0;">
                                                @csrf
                                                <button class="btn btn-danger" type="submit">Delete Booking</button>
                                            </form>
                                        @endif
                                    </div>

                                    <div id="rsv-detail-{{ $rsvId }}" class="reservation-detail-row" hidden>
                                        <div style="padding:10px 12px;">
                                            <div class="reservation-detail-grid">
                                                <div>
                                                    <p class="small" style="margin:0;font-weight:700;color:#1f3e59;">Payment Breakdown</p>
                                                    <p class="small" style="margin:3px 0 0;">Subtotal: {{ number_format((float) ($reservationRow['subtotal_amount'] ?? 0), 2) }} {{ (string) ($reservationRow['currency'] ?? 'MVR') }}</p>
                                                    <p class="small" style="margin:3px 0 0;">Tax: {{ number_format((float) ($reservationRow['total_tax_amount'] ?? 0), 2) }} | Service: {{ number_format((float) ($reservationRow['service_charge_total'] ?? 0), 2) }}</p>
                                                    <p class="small" style="margin:3px 0 0;">Invoice total: {{ number_format((float) ($reservationRow['invoice_total_amount'] ?? 0), 2) }} {{ (string) ($reservationRow['currency'] ?? 'MVR') }}</p>
                                                </div>
                                                <div>
                                                    <p class="small" style="margin:0;font-weight:700;color:#1f3e59;">Payout & Refund</p>
                                                    <p class="small" style="margin:3px 0 0;">Payout: {{ strtoupper((string) ($reservationRow['payout_status'] ?? 'queued')) }}{{ trim((string) ($reservationRow['payout_expected_at'] ?? '')) !== '' ? (' | ETA ' . substr((string) ($reservationRow['payout_expected_at'] ?? ''), 0, 10)) : '' }}</p>
                                                    <p class="small" style="margin:3px 0 0;">Refund case: {{ (bool) ($reservationRow['has_refund_case'] ?? false) ? 'Yes' : 'No' }}{{ trim((string) ($reservationRow['refund_status'] ?? '')) !== '' ? (' | ' . strtoupper((string) ($reservationRow['refund_status'] ?? ''))) : '' }}</p>
                                                    <p class="small" style="margin:3px 0 0;">Open dispute: {{ (bool) ($reservationRow['has_open_dispute'] ?? false) ? 'Yes' : 'No' }}</p>
                                                </div>
                                                <div>
                                                    <p class="small" style="margin:0;font-weight:700;color:#1f3e59;">Operational Timeline</p>
                                                    <p class="small" style="margin:3px 0 0;">Booked at: {{ trim((string) ($reservationRow['created_at'] ?? '')) !== '' ? substr((string) ($reservationRow['created_at'] ?? ''), 0, 16) : 'N/A' }}</p>
                                                    <p class="small" style="margin:3px 0 0;">Stay window: {{ trim((string) ($reservationRow['start_at'] ?? '')) !== '' ? substr((string) ($reservationRow['start_at'] ?? ''), 0, 10) : '-' }} to {{ trim((string) ($reservationRow['end_at'] ?? '')) !== '' ? substr((string) ($reservationRow['end_at'] ?? ''), 0, 10) : '-' }}</p>
                                                    <p class="small" style="margin:3px 0 0;">Payment ref: {{ trim((string) ($reservationRow['payment_reference'] ?? '')) !== '' ? (string) ($reservationRow['payment_reference'] ?? '') : 'N/A' }}</p>
                                                    @if ($rsvId > 0)
                                                        <div class="reservation-print-actions">
                                                            <a href="{{ url('/vendor/print/reservation/' . $rsvId) }}" target="_blank" rel="noopener">Print Reservation</a>
                                                            <a href="{{ url('/vendor/print/invoice/' . $rsvId) }}" target="_blank" rel="noopener">Print Invoice</a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="ops-empty">No reservations for {{ strtolower($labelForCategory($categoryKey)) }} in {{ $reservationScope }} scope.</div>
                            @endforelse
                        </div>

                        <details class="vendor-reservation-advanced">
                            <summary>Open Advanced Reservation Table View</summary>
                            <div class="ops-table-wrap reservation-table-wrap">
                            <table class="ops-table is-compact reservation-ops-table reservation-ops-table--enterprise" aria-label="{{ $labelForCategory($categoryKey) }} reservations table">
                                <thead>
                                    <tr>
                                        <th class="reservation-cell-sticky-left">Booking / Reservation</th>
                                        <th>Guest Information</th>
                                        @if ($categoryKey === 'excursion')
                                            <th>Participants</th>
                                            <th>Activity Date</th>
                                            <th>Activity / Listing</th>
                                            <th>Departure Details</th>
                                        @else
                                            <th>Occupancy</th>
                                            <th>Stay</th>
                                            <th>Service / Room</th>
                                            <th>Meal Plan</th>
                                            <th>Transfer Option</th>
                                        @endif
                                        <th>Payment Status</th>
                                        <th>Status</th>
                                        <th>Special Request</th>
                                        <th class="reservation-cell-sticky-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($reservationScopeFiltered->take(30) as $reservationRow)
                                        @php
                                            $rsvId = (int) ($reservationRow['id'] ?? 0);
                                            $adults = max(1, (int) ($reservationRow['adult_guests'] ?? 1));
                                            $children = max(0, (int) ($reservationRow['child_guests'] ?? 0));
                                            $infants = max(0, (int) ($reservationRow['infant_guests'] ?? 0));
                                            $bookedGuests = max(1, (int) ($reservationRow['guests'] ?? 1));
                                            $totalGuests = max($bookedGuests, ($adults + $children + $infants));
                                            if (($children + $infants) === 0 && $adults < $totalGuests) {
                                                $adults = $totalGuests;
                                            }
                                            $nights = 0;
                                            try {
                                                $startDate = new \DateTimeImmutable((string) ($reservationRow['start_at'] ?? ''));
                                                $endDate = new \DateTimeImmutable((string) ($reservationRow['end_at'] ?? ''));
                                                $nights = max(0, (int) $startDate->diff($endDate)->days);
                                            } catch (\Exception $ignored) {
                                                $nights = 0;
                                            }
                                            $checkInDateLabel = trim((string) ($reservationRow['start_at'] ?? '')) !== ''
                                                ? substr((string) ($reservationRow['start_at'] ?? ''), 0, 10)
                                                : '-';
                                            $checkOutDateLabel = trim((string) ($reservationRow['end_at'] ?? '')) !== ''
                                                ? substr((string) ($reservationRow['end_at'] ?? ''), 0, 10)
                                                : '-';
                                        @endphp
                                        <tr>
                                            <td class="reservation-cell-sticky-left">
                                                {{ (string) ($reservationRow['target_label'] ?? 'Global / Unlinked') }}<br>
                                                Ref: {{ (string) ($reservationRow['reservation_code'] ?? ('RSV-' . str_pad((string) (int) ($reservationRow['id'] ?? 0), 6, '0', STR_PAD_LEFT))) }}
                                            </td>
                                            <td>
                                                {{ (string) ($reservationRow['customer_name'] ?? '') }}<br>
                                                {{ (string) ($reservationRow['customer_email'] ?? '') }}<br>
                                                Nationality: {{ (string) ($reservationRow['primary_nationality'] ?? 'Unknown') }}
                                            </td>
                                            @if ($categoryKey === 'excursion')
                                                <td>
                                                    {{ $totalGuests }} total<br>
                                                    A{{ $adults }} / C{{ $children }} / I{{ $infants }}
                                                </td>
                                                <td>
                                                    {{ $checkInDateLabel }}
                                                </td>
                                                <td>
                                                    {{ (string) ($reservationRow['room_label'] ?? $reservationRow['target_label'] ?? 'N/A') }}
                                                </td>
                                                <td>
                                                    @if (trim((string) ($reservationRow['departure_area'] ?? '')) !== '')
                                                        Area: {{ (string) ($reservationRow['departure_area'] ?? '') }}<br>
                                                    @endif
                                                    @if (trim((string) ($reservationRow['departure_time_booked'] ?? '')) !== '')
                                                        Time: {{ (string) ($reservationRow['departure_time_booked'] ?? '') }}<br>
                                                    @endif
                                                    @if (trim((string) ($reservationRow['return_slot'] ?? '')) !== '')
                                                        Return: {{ (string) ($reservationRow['return_slot'] ?? '') }}
                                                    @endif
                                                    @if (trim((string) ($reservationRow['departure_area'] ?? '')) === '' && trim((string) ($reservationRow['departure_time_booked'] ?? '')) === '' && trim((string) ($reservationRow['return_slot'] ?? '')) === '')
                                                        —
                                                    @endif
                                                </td>
                                            @else
                                                <td>
                                                    {{ $totalGuests }} total<br>
                                                    A{{ $adults }} / C{{ $children }} / I{{ $infants }}
                                                </td>
                                                <td>
                                                    Check-in: {{ $checkInDateLabel }}<br>
                                                    Check-out: {{ $checkOutDateLabel }}<br>
                                                    Nights: {{ $nights }}
                                                </td>
                                                <td>
                                                    {{ (string) ($reservationRow['room_label'] ?? $reservationRow['target_label'] ?? 'N/A') }}
                                                </td>
                                                <td>
                                                    {{ (string) ($reservationRow['meal_plan'] ?? 'RO') }}
                                                </td>
                                                <td>
                                                    {{ (string) ($reservationRow['transfer_method'] ?? 'Not selected') }}
                                                </td>
                                            @endif
                                            <td>
                                                @php
                                                    $paymentStatusValue = strtolower(trim((string) ($reservationRow['payment_status'] ?? 'unpaid')));
                                                    $paymentStatusClass = in_array($paymentStatusValue, ['paid', 'captured', 'settled'], true)
                                                        ? 'ok'
                                                        : (in_array($paymentStatusValue, ['partially_paid', 'pending'], true) ? 'warn' : 'err');
                                                @endphp
                                                <span class="status-pill {{ $paymentStatusClass }}">{{ strtoupper((string) ($reservationRow['payment_status'] ?? 'unpaid')) }}</span>
                                            </td>
                                            <td>
                                                @php
                                                    $bookingStatusValue = strtolower(trim((string) ($reservationRow['status'] ?? 'pending')));
                                                    $bookingStatusClass = in_array($bookingStatusValue, ['confirmed', 'checked_in', 'checked_out', 'completed'], true)
                                                        ? 'ok'
                                                        : (in_array($bookingStatusValue, ['pending', 'cancel_requested'], true) ? 'warn' : 'err');
                                                @endphp
                                                <span class="status-pill {{ $bookingStatusClass }}">{{ strtoupper((string) ($reservationRow['status'] ?? 'pending')) }}</span>
                                            </td>
                                            <td>
                                                {{ trim((string) ($reservationRow['special_request'] ?? '')) !== '' ? (string) ($reservationRow['special_request'] ?? '') : 'None' }}
                                            </td>
                                            <td class="reservation-cell-sticky-right">
                                                @php
                                                    $rowStatus = strtolower(trim((string) ($reservationRow['status'] ?? 'pending')));
                                                    $rowPaymentStatus = strtolower(trim((string) ($reservationRow['payment_status'] ?? 'unpaid')));
                                                    $timelineOptions = [
                                                        'pending' => 'Booked (Pending Confirmation)',
                                                        'cancel_requested' => 'Cancel Requested (Customer)',
                                                        'confirmed' => 'Confirmed',
                                                        'checked_in' => 'Guest Checked-In',
                                                        'checked_out' => 'Guest Checked-Out',
                                                        'completed' => 'Stay Completed (Ready for Payout)',
                                                        'cancelled' => 'Cancelled',
                                                    ];
                                                    $payoutStatusText = strtoupper((string) ($reservationRow['payout_status'] ?? 'queued'));
                                                    $canDeleteReservation = in_array($rowStatus, ['cancelled', 'failed', 'expired', 'rejected'], true)
                                                        && in_array($rowPaymentStatus, ['unpaid', 'failed', 'cancelled', 'refunded'], true)
                                                        && !((bool) ($reservationRow['has_open_dispute'] ?? false))
                                                        && !((bool) ($reservationRow['has_refund_case'] ?? false));
                                                @endphp
                                                <button
                                                    type="button"
                                                    class="btn btn-secondary reservation-row-toggle"
                                                    data-reservation-row-toggle="adv-rsv-{{ $rsvId }}"
                                                    aria-expanded="false"
                                                    aria-controls="adv-rsv-detail-{{ $rsvId }}"
                                                >Details</button>
                                                <form class="inline-status-form" method="POST" action="/portal/vendor/reservations/{{ (int) ($reservationRow['id'] ?? 0) }}/status">
                                                    @csrf
                                                    <select class="ops-select" name="status" required>
                                                        @foreach ($timelineOptions as $timelineValue => $timelineLabel)
                                                            <option value="{{ $timelineValue }}" @selected($rowStatus === $timelineValue)>{{ $timelineLabel }}</option>
                                                        @endforeach
                                                    </select>
                                                    <textarea
                                                        name="cancel_reason"
                                                        class="ops-input"
                                                        rows="2"
                                                        maxlength="1000"
                                                        placeholder="Cancellation reason (required for paid booking cancellation request)"
                                                    >{{ old('cancel_reason', '') }}</textarea>
                                                    <button class="btn btn-secondary" type="submit">Save Timeline</button>
                                                </form>
                                                @if ($canDeleteReservation)
                                                    <form method="POST" action="/portal/vendor/reservations/{{ (int) ($reservationRow['id'] ?? 0) }}/delete" onsubmit="return confirm('Remove this cancelled booking from your vendor portal list?');" style="margin-top:8px;">
                                                        @csrf
                                                        <button class="btn btn-danger" type="submit">Delete Booking</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr id="adv-rsv-detail-{{ $rsvId }}" class="reservation-detail-row" hidden>
                                            <td colspan="{{ $categoryKey === 'excursion' ? 10 : 11 }}">
                                                <div class="reservation-detail-grid">
                                                    <div>
                                                        <p class="small" style="margin:0;font-weight:700;color:#1f3e59;">Payment Breakdown</p>
                                                        <p class="small" style="margin:3px 0 0;">Subtotal: {{ number_format((float) ($reservationRow['subtotal_amount'] ?? 0), 2) }} {{ (string) ($reservationRow['currency'] ?? 'MVR') }}</p>
                                                        <p class="small" style="margin:3px 0 0;">Tax: {{ number_format((float) ($reservationRow['total_tax_amount'] ?? 0), 2) }} | Service: {{ number_format((float) ($reservationRow['service_charge_total'] ?? 0), 2) }}</p>
                                                        <p class="small" style="margin:3px 0 0;">Invoice total: {{ number_format((float) ($reservationRow['invoice_total_amount'] ?? 0), 2) }} {{ (string) ($reservationRow['currency'] ?? 'MVR') }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="small" style="margin:0;font-weight:700;color:#1f3e59;">Payout & Refund</p>
                                                        <p class="small" style="margin:3px 0 0;">Payout: {{ strtoupper((string) ($reservationRow['payout_status'] ?? 'queued')) }}{{ trim((string) ($reservationRow['payout_expected_at'] ?? '')) !== '' ? (' | ETA ' . substr((string) ($reservationRow['payout_expected_at'] ?? ''), 0, 10)) : '' }}</p>
                                                        <p class="small" style="margin:3px 0 0;">Refund case: {{ (bool) ($reservationRow['has_refund_case'] ?? false) ? 'Yes' : 'No' }}{{ trim((string) ($reservationRow['refund_status'] ?? '')) !== '' ? (' | ' . strtoupper((string) ($reservationRow['refund_status'] ?? ''))) : '' }}</p>
                                                        <p class="small" style="margin:3px 0 0;">Open dispute: {{ (bool) ($reservationRow['has_open_dispute'] ?? false) ? 'Yes' : 'No' }}</p>
                                                    </div>
                                                    <div>
                                                        <p class="small" style="margin:0;font-weight:700;color:#1f3e59;">Operational Timeline</p>
                                                        <p class="small" style="margin:3px 0 0;">Booked at: {{ trim((string) ($reservationRow['created_at'] ?? '')) !== '' ? substr((string) ($reservationRow['created_at'] ?? ''), 0, 16) : 'N/A' }}</p>
                                                        <p class="small" style="margin:3px 0 0;">Stay window: {{ trim((string) ($reservationRow['start_at'] ?? '')) !== '' ? substr((string) ($reservationRow['start_at'] ?? ''), 0, 10) : '-' }} to {{ trim((string) ($reservationRow['end_at'] ?? '')) !== '' ? substr((string) ($reservationRow['end_at'] ?? ''), 0, 10) : '-' }}</p>
                                                        <p class="small" style="margin:3px 0 0;">Payment ref: {{ trim((string) ($reservationRow['payment_reference'] ?? '')) !== '' ? (string) ($reservationRow['payment_reference'] ?? '') : 'N/A' }}</p>
                                                        @if ($rsvId > 0)
                                                            <div class="reservation-print-actions">
                                                                <a href="{{ url('/vendor/print/reservation/' . $rsvId) }}" target="_blank" rel="noopener">Print Reservation</a>
                                                                <a href="{{ url('/vendor/print/invoice/' . $rsvId) }}" target="_blank" rel="noopener">Print Invoice</a>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $categoryKey === 'excursion' ? 10 : 11 }}" class="ops-empty">No reservations for {{ strtolower($labelForCategory($categoryKey)) }} in {{ $reservationScope }} scope.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            </div>
                        </details>

                        @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

<script>
(function () {
    if (window.__vendorReservationRowToggleBound) {
        return;
    }
    window.__vendorReservationRowToggleBound = true;

    document.addEventListener('click', function (event) {
        const toggle = event.target.closest('[data-reservation-row-toggle]');
        if (!toggle) {
            return;
        }

        const rowKey = String(toggle.getAttribute('data-reservation-row-toggle') || '').trim();
        if (rowKey === '') {
            return;
        }

        let detailId = '';
        if (rowKey.indexOf('adv-rsv-') === 0) {
            detailId = 'adv-rsv-detail-' + rowKey.replace('adv-rsv-', '');
        } else if (rowKey.indexOf('rsv-') === 0) {
            detailId = 'rsv-detail-' + rowKey.replace('rsv-', '');
        }
        const detail = detailId !== '' ? document.getElementById(detailId) : null;
        if (!detail) {
            return;
        }

        const nextExpanded = detail.hidden;
        detail.hidden = !nextExpanded;
        toggle.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
        toggle.textContent = nextExpanded ? 'Hide' : 'Details';
    });
})();
</script>