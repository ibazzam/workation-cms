@php
    $operationsViewMode = in_array((string) ($operationsViewMode ?? 'reservations'), ['reservations', 'availability'], true)
        ? (string) $operationsViewMode
        : 'reservations';
    $reservationScope = strtolower(trim((string) request()->query('scope', 'active')));
    if (!in_array($reservationScope, ['active', 'pending', 'history', 'all'], true)) {
        $reservationScope = 'active';
    }
    $showAvailabilityPanel = $operationsViewMode === 'availability';
    $showReservationsPanel = $operationsViewMode === 'reservations';

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
<section id="vendorAvailabilitySection" class="card ops-section" aria-label="Vendor availability calendar" data-panel-group="reservations">
            <div class="ops-header">
                <p class="ops-title">{{ $showAvailabilityPanel ? 'Availability Operations' : 'Reservation Operations' }}</p>
                <span class="ops-chip">{{ count($allVendorCategoryKeys ?? []) }} categories</span>
            </div>
            @if (empty($allVendorCategoryKeys))
                <p class="wizard-note" style="margin-bottom:10px;">Availability and reservation controls are locked until at least one category is approved by admin.</p>
            @endif
            <div class="panel-links" aria-label="Category operations actions">
                @if ($showReservationsPanel)
                    <a href="{{ '/vendor/reservations' . ($forcedListingCategory !== '' ? ('?category=' . urlencode((string) $forcedListingCategory) . '&scope=active') : '?scope=active') }}">Active</a>
                    <a href="{{ '/vendor/reservations' . ($forcedListingCategory !== '' ? ('?category=' . urlencode((string) $forcedListingCategory) . '&scope=pending') : '?scope=pending') }}">Pending</a>
                    <a href="{{ '/vendor/reservations' . ($forcedListingCategory !== '' ? ('?category=' . urlencode((string) $forcedListingCategory) . '&scope=history') : '?scope=history') }}">History</a>
                    <a href="{{ '/vendor/reservations' . ($forcedListingCategory !== '' ? ('?category=' . urlencode((string) $forcedListingCategory) . '&scope=all') : '?scope=all') }}">All</a>
                @endif
                @if ($showAvailabilityPanel)
                    <a href="#vendorAvailabilitySection">Availability</a>
                @endif
                <a href="{{ '/vendor/pricing' . ($forcedListingCategory !== '' ? ('?category=' . urlencode((string) $forcedListingCategory)) : '') }}">Pricing Rules</a>
            </div>
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

                    $availabilityTargetsByCategory[$categoryKey]->push([
                        'kind' => 'property',
                        'id' => $propertyId,
                        'property_id' => $propertyId,
                        'service_id' => '',
                        'room_id' => '',
                        'route_name' => '',
                        'label' => 'Property #' . $propertyId . ' - ' . (string) ($property->name ?? ('Property ' . $propertyId)),
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
                        $categoryKey = 'accommodation';
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

                    // For accommodation, only room-level reservations are valid in this operations view.
                    if ($reservationCategory === 'accommodation' && $reservationRoomId <= 0) {
                        continue;
                    }

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
                        'id' => (int) ($reservation->id ?? 0),
                        'target_label' => $reservationTargetLabel,
                        'target_value' => $reservationTargetValue,
                        'room_label' => trim((string) ($reservationNotes['room_name'] ?? $reservationTargetLabel)),
                        'meal_plan' => trim((string) ($reservationNotes['meal_plan_label'] ?? $reservationNotes['meal_plan'] ?? 'Not specified')),
                        'transfer_method' => trim((string) ($reservationNotes['transfer_option_label'] ?? $reservationNotes['transfer_option'] ?? 'Not selected')),
                        'special_request' => trim((string) ($reservationNotes['service_notes'] ?? $reservationNotes['additional_guest_details'] ?? '')),
                        'customer_name' => (string) ($reservation->customer_name ?? ''),
                        'customer_email' => (string) ($reservation->customer_email ?? ''),
                        'primary_nationality' => trim((string) ($reservationNotes['primary_nationality'] ?? 'Unknown')),
                        'adult_guests' => max(1, (int) ($reservation->adult_guests ?? ($reservationNotes['adults'] ?? $reservation->guests ?? 1))),
                        'child_guests' => max(0, (int) ($reservation->child_guests ?? ($reservationNotes['children'] ?? 0))),
                        'infant_guests' => max(0, (int) ($reservationNotes['infants'] ?? 0)),
                        'payment_gateway' => (string) ($reservation->payment_gateway ?? ''),
                        'payment_currency' => (string) ($reservation->payment_currency ?? $reservation->currency ?? 'MVR'),
                        'payment_reference' => (string) ($reservation->payment_reference ?? ''),
                        'start_at' => (string) ($reservation->start_at ?? ''),
                        'end_at' => (string) ($reservation->end_at ?? ''),
                        'status' => (string) ($reservation->status ?? 'pending'),
                        'payment_status' => (string) ($reservation->payment_status ?? 'unpaid'),
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
                        if ($categoryKey === 'accommodation') {
                            $accommodationRoomTargetsByProperty = $categoryTargets
                                ->filter(static fn ($target) => (string) ($target['kind'] ?? '') === 'room')
                                ->groupBy(static fn ($target) => (string) ($target['property_name'] ?? ('Property #' . (string) ($target['property_id'] ?? ''))))
                                ->sortKeys();
                        }
                        $categorySlots = ($availabilityRowsByCategory[$categoryKey] ?? collect())->sortByDesc('slot_date')->values();
                        $categoryReservations = ($reservationRowsByCategory[$categoryKey] ?? collect())->sortByDesc('start_at')->values();
                        $trackedCount = $categorySlots->count();
                        $closedCount = $categorySlots->where('is_closed', true)->count();
                        $openCount = max(0, $trackedCount - $closedCount);
                        $inventoryTotal = (int) $categorySlots->sum('inventory');
                        $reservationPendingCount = $categoryReservations->where('status', 'pending')->count();
                        $reservationConfirmedCount = $categoryReservations->where('status', 'confirmed')->count();
                        $reservationCompletedCount = $categoryReservations->where('status', 'completed')->count();
                        $reservationCancelledCount = $categoryReservations->where('status', 'cancelled')->count();
                        $reservationRevenueTotal = (float) $categoryReservations->sum('invoice_total_amount');
                    @endphp
                    <article class="ops-category-card" data-ops-category-section="category-operations-{{ $categoryKey }}">
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
                        <p class="ops-subtitle">Listings in {{ $labelForCategory($categoryKey) }} (click to edit availability)</p>
                        @if ($categoryKey === 'accommodation')
                            @if ($accommodationRoomTargetsByProperty->isEmpty())
                                <div class="ops-target-quicklist">
                                    <span class="small">No rooms yet under accommodation properties.</span>
                                </div>
                            @else
                                @foreach ($accommodationRoomTargetsByProperty as $propertyName => $propertyRooms)
                                    <p class="small" style="margin:8px 0 4px;"><strong>{{ (string) $propertyName }}</strong></p>
                                    <div class="ops-target-quicklist">
                                        @foreach ($propertyRooms as $targetOption)
                                            @php
                                                $targetKind = (string) ($targetOption['kind'] ?? '');
                                                $targetId = (string) ($targetOption['id'] ?? '');
                                                $targetValue = $targetKind !== '' && $targetId !== '' ? ($targetKind . ':' . $targetId) : '';
                                            @endphp
                                            @if ($targetValue !== '')
                                                <button
                                                    type="button"
                                                    class="ops-target-quickpick"
                                                    data-availability-pick-target
                                                    data-availability-form-key="{{ $categoryKey }}"
                                                    data-target-value="{{ $targetValue }}"
                                                >{{ (string) ($targetOption['room_name'] ?? ('Room ' . $targetId)) }}</button>
                                            @endif
                                        @endforeach
                                    </div>
                                @endforeach
                            @endif
                        @else
                            <div class="ops-target-quicklist">
                                @forelse ($categoryTargets as $targetOption)
                                    @php
                                        $targetKind = (string) ($targetOption['kind'] ?? '');
                                        $targetId = (string) ($targetOption['id'] ?? '');
                                        $targetValue = $targetKind !== '' && $targetId !== '' ? ($targetKind . ':' . $targetId) : '';
                                    @endphp
                                    @if ($targetValue !== '')
                                        <button
                                            type="button"
                                            class="ops-target-quickpick"
                                            data-availability-pick-target
                                            data-availability-form-key="{{ $categoryKey }}"
                                            data-target-value="{{ $targetValue }}"
                                        >{{ (string) ($targetOption['label'] ?? $targetValue) }}</button>
                                    @endif
                                @empty
                                    <span class="small">No listings yet in this category.</span>
                                @endforelse
                            </div>
                        @endif
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
                        <div class="ops-grid">
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
                                <p class="small availability-inline-note">Transfer and tariff changes are managed from <a href="#vendorPricingSection">Pricing Rules</a>.</p>
                            </form>

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
                                <table class="ops-table" aria-label="{{ $labelForCategory($categoryKey) }} availability table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Target</th>
                                            <th>Route / Service</th>
                                            <th>Inventory</th>
                                            <th>Reserved</th>
                                            <th>Closed</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($categorySlots->take(20) as $slotRow)
                                            <tr>
                                                <td>{{ (string) ($slotRow['slot_date'] ?? '-') }}</td>
                                                <td>
                                                    @if ((string) ($slotRow['target_value'] ?? '') !== '')
                                                        <button
                                                            type="button"
                                                            class="ops-target-quickpick"
                                                            data-availability-pick-target
                                                            data-availability-form-key="{{ $categoryKey }}"
                                                            data-target-value="{{ (string) ($slotRow['target_value'] ?? '') }}"
                                                        >{{ (string) ($slotRow['target_label'] ?? 'N/A') }}</button>
                                                    @else
                                                        {{ (string) ($slotRow['target_label'] ?? 'N/A') }}
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ (string) ($slotRow['route_name'] ?? '') !== '' ? (string) ($slotRow['route_name'] ?? '') : 'N/A' }}
                                                    @if ((int) ($slotRow['service_id'] ?? 0) > 0)
                                                        <br><span class="small">Service ID {{ (int) ($slotRow['service_id'] ?? 0) }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ (int) ($slotRow['inventory'] ?? 0) }}</td>
                                                <td>{{ (int) ($slotRow['reserved_count'] ?? 0) }}</td>
                                                <td>{{ (bool) ($slotRow['is_closed'] ?? false) ? 'YES' : 'NO' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="ops-empty">No availability slots for {{ strtolower($labelForCategory($categoryKey)) }} yet. Select a target above and block/open dates using the calendar.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @php
                            $reservationScopeFiltered = $categoryReservations->filter(static function (array $row) use ($reservationScope): bool {
                                $status = strtolower(trim((string) ($row['status'] ?? 'pending')));
                                $paymentStatus = strtolower(trim((string) ($row['payment_status'] ?? 'unpaid')));

                                return match ($reservationScope) {
                                    'active' => in_array($status, ['confirmed', 'upcoming'], true) && $paymentStatus === 'paid',
                                    'pending' => in_array($status, ['pending'], true) || in_array($paymentStatus, ['unpaid', 'partially_paid'], true),
                                    'history' => in_array($status, ['cancelled', 'completed', 'expired', 'failed', 'rejected'], true) || in_array($paymentStatus, ['refunded'], true),
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
                                <p class="metric-label">Completed</p>
                                <p class="metric-value">{{ $reservationScopeFiltered->where('status', 'completed')->count() }}</p>
                            </article>
                            <article class="billing-ledger-card">
                                <p class="metric-label">Cancelled</p>
                                <p class="metric-value">{{ $reservationScopeFiltered->where('status', 'cancelled')->count() }}</p>
                            </article>
                            <article class="billing-ledger-card">
                                <p class="metric-label">Booked Revenue</p>
                                <p class="metric-value">MVR {{ number_format((float) $reservationScopeFiltered->sum('invoice_total_amount'), 2) }}</p>
                            </article>
                        </div>

                        <div class="ops-table-wrap">
                            <table class="ops-table" aria-label="{{ $labelForCategory($categoryKey) }} reservations table">
                                <thead>
                                    <tr>
                                        <th>Booking</th>
                                        <th>Guest</th>
                                        <th>Stay</th>
                                        <th>Service</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($reservationScopeFiltered->take(30) as $reservationRow)
                                        @php
                                            $adults = max(1, (int) ($reservationRow['adult_guests'] ?? 1));
                                            $children = max(0, (int) ($reservationRow['child_guests'] ?? 0));
                                            $infants = max(0, (int) ($reservationRow['infant_guests'] ?? 0));
                                            $totalGuests = $adults + $children + $infants;
                                            $nights = 0;
                                            try {
                                                $startDate = new \DateTimeImmutable((string) ($reservationRow['start_at'] ?? ''));
                                                $endDate = new \DateTimeImmutable((string) ($reservationRow['end_at'] ?? ''));
                                                $nights = max(0, (int) $startDate->diff($endDate)->days);
                                            } catch (\Exception $ignored) {
                                                $nights = 0;
                                            }
                                        @endphp
                                        <tr>
                                            <td>
                                                {{ (string) ($reservationRow['target_label'] ?? 'Global / Unlinked') }}<br>
                                                Ref: #{{ (int) ($reservationRow['id'] ?? 0) }}
                                            </td>
                                            <td>
                                                {{ (string) ($reservationRow['customer_name'] ?? '') }}<br>
                                                {{ (string) ($reservationRow['customer_email'] ?? '') }}<br>
                                                Guests: {{ $totalGuests }} (A{{ $adults }} / C{{ $children }} / I{{ $infants }})<br>
                                                Nationality: {{ (string) ($reservationRow['primary_nationality'] ?? 'Unknown') }}
                                            </td>
                                            <td>
                                                Check-in: {{ (string) ($reservationRow['start_at'] ?? '-') }}<br>
                                                Check-out: {{ (string) ($reservationRow['end_at'] ?? '-') }}<br>
                                                Nights: {{ $nights }}
                                            </td>
                                            <td>
                                                Room: {{ (string) ($reservationRow['room_label'] ?? $reservationRow['target_label'] ?? 'N/A') }}<br>
                                                Meal Plan: {{ (string) ($reservationRow['meal_plan'] ?? 'Not specified') }}<br>
                                                Transfer: {{ (string) ($reservationRow['transfer_method'] ?? 'Not selected') }}<br>
                                                Special Request: {{ trim((string) ($reservationRow['special_request'] ?? '')) !== '' ? (string) ($reservationRow['special_request'] ?? '') : 'None' }}
                                            </td>
                                            <td>
                                                Payment Status: {{ strtoupper((string) ($reservationRow['payment_status'] ?? 'unpaid')) }}<br>
                                                Paid Amount: {{ (string) ($reservationRow['payment_currency'] ?? $reservationRow['currency'] ?? 'MVR') }} {{ number_format((float) ($reservationRow['paid_amount'] ?? 0), 2) }}<br>
                                                Payment Ref: {{ trim((string) ($reservationRow['payment_reference'] ?? '')) !== '' ? (string) ($reservationRow['payment_reference'] ?? '') : 'N/A' }}
                                            </td>
                                            <td>
                                                <form class="inline-status-form" method="POST" action="/portal/vendor/reservations/{{ (int) ($reservationRow['id'] ?? 0) }}/status">
                                                    @csrf
                                                    <select class="ops-select" name="status" required>
                                                        <option value="pending" @selected(($reservationRow['status'] ?? '') === 'pending')>Pending</option>
                                                        <option value="confirmed" @selected(($reservationRow['status'] ?? '') === 'confirmed')>Confirmed</option>
                                                        <option value="cancelled" @selected(($reservationRow['status'] ?? '') === 'cancelled')>Cancelled</option>
                                                        <option value="completed" @selected(($reservationRow['status'] ?? '') === 'completed')>Completed</option>
                                                    </select>
                                                    <select class="ops-select" name="payment_status" required>
                                                        <option value="unpaid" @selected(($reservationRow['payment_status'] ?? '') === 'unpaid')>Unpaid</option>
                                                        <option value="partially_paid" @selected(($reservationRow['payment_status'] ?? '') === 'partially_paid')>Partially Paid</option>
                                                        <option value="paid" @selected(($reservationRow['payment_status'] ?? '') === 'paid') @disabled((bool) ($reservationRow['is_online_gateway'] ?? false))>Paid</option>
                                                        <option value="refunded" @selected(($reservationRow['payment_status'] ?? '') === 'refunded')>Refunded</option>
                                                    </select>
                                                    <button class="btn btn-secondary" type="submit">Save Status</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="ops-empty">No reservations for {{ strtolower($labelForCategory($categoryKey)) }} in {{ $reservationScope }} scope.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>