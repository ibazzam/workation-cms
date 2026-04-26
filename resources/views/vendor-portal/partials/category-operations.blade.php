<section id="vendorAvailabilitySection" class="card ops-section" aria-label="Vendor availability calendar" data-panel-group="reservations">
            <div class="ops-header">
                <p class="ops-title">Category Operations</p>
                <span class="ops-chip">{{ count($allVendorCategoryKeys ?? []) }} categories</span>
            </div>
            <div class="panel-links" aria-label="Category operations actions">
                <a href="#vendorAvailabilitySection">Availability + Reservations</a>
                <a href="#vendorPricingSection">Pricing Rules</a>
            </div>
            @php
                $allVendorCategoryKeys = array_keys($vendorCategoryMap);
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

                    // Accommodation availability is managed at room level only.
                    if ($categoryKey === 'accommodation') {
                        continue;
                    }

                    $listingCountByCategory[$categoryKey]++;
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

                    $reservationPropertyId = (int) ($reservation->vendor_property_id ?? 0);
                    $reservationServiceId = (int) ($reservation->vendor_service_id ?? 0);
                    $reservationRoomId = (int) ($reservation->vendor_room_category_id ?? 0);
                    $reservationCategory = vendorPortalCanonicalCategory((string) ($reservation->listing_category ?? ''));

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
                        'customer_name' => (string) ($reservation->customer_name ?? ''),
                        'customer_email' => (string) ($reservation->customer_email ?? ''),
                        'start_at' => (string) ($reservation->start_at ?? ''),
                        'end_at' => (string) ($reservation->end_at ?? ''),
                        'status' => (string) ($reservation->status ?? 'pending'),
                        'payment_status' => (string) ($reservation->payment_status ?? 'unpaid'),
                        'currency' => (string) ($reservation->currency ?? 'MVR'),
                        'subtotal_amount' => (float) ($reservation->subtotal_amount ?? $reservation->total_amount ?? 0),
                        'service_charge_total' => (float) ($reservation->service_charge_total ?? 0),
                        'total_tax_amount' => (float) ($reservation->total_tax_amount ?? 0),
                        'invoice_total_amount' => (float) ($reservation->invoice_total_amount ?? $reservation->total_amount ?? 0),
                        'green_tax_total' => (float) ($reservation->green_tax_total ?? 0),
                        'tgst_total' => (float) ($reservation->tgst_total ?? 0),
                        'cgst_total' => (float) ($reservation->cgst_total ?? 0),
                        'room_pricing' => $roomPricingBreakdown,
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
                                        <label for="availability_target_{{ $categorySlug }}">Listing / Product / Room</label>
                                        <select id="availability_target_{{ $categorySlug }}" class="ops-select" data-availability-target>
                                            @if ($categoryKey === 'accommodation')
                                                <option value="">Select room for accommodation availability</option>
                                            @else
                                                <option value="">Generic slot for {{ $labelForCategory($categoryKey) }}</option>
                                            @endif
                                            @foreach ($categoryTargets as $targetOption)
                                                <option
                                                    value="{{ (string) ($targetOption['kind'] ?? '') }}:{{ (string) ($targetOption['id'] ?? '') }}"
                                                    data-property-id="{{ (string) ($targetOption['property_id'] ?? '') }}"
                                                    data-service-id="{{ (string) ($targetOption['service_id'] ?? '') }}"
                                                    data-room-id="{{ (string) ($targetOption['room_id'] ?? '') }}"
                                                    data-route-name="{{ (string) ($targetOption['route_name'] ?? '') }}"
                                                >{{ (string) ($targetOption['label'] ?? '') }}</option>
                                            @endforeach
                                        </select>
                                        <p class="small">Manage only {{ strtolower($labelForCategory($categoryKey)) }} listings in this section.</p>
                                    </div>
                                    <div class="ops-field">
                                        <label for="availability_date_{{ $categorySlug }}">Date</label>
                                        <input id="availability_date_{{ $categorySlug }}" name="slot_date" class="ops-input" type="date">
                                    </div>
                                    <div class="ops-field">
                                        <label for="availability_from_{{ $categorySlug }}">Range From (optional)</label>
                                        <input id="availability_from_{{ $categorySlug }}" name="apply_range_from" class="ops-input" type="date">
                                    </div>
                                    <div class="ops-field">
                                        <label for="availability_to_{{ $categorySlug }}">Range To (optional)</label>
                                        <input id="availability_to_{{ $categorySlug }}" name="apply_range_to" class="ops-input" type="date">
                                    </div>
                                    <div class="ops-field">
                                        <label for="availability_schedule_profile_{{ $categorySlug }}">Schedule Profile</label>
                                        <select id="availability_schedule_profile_{{ $categorySlug }}" name="schedule_profile" class="ops-select">
                                            <option value="one_off">One-off day</option>
                                            <option value="daily">Daily in selected range</option>
                                            <option value="weekly_6">Weekly 6 days (Mon-Sat)</option>
                                            <option value="weekly_3">Weekly 3 days (default Mon/Wed/Fri)</option>
                                            <option value="weekly_custom">Weekly custom days</option>
                                        </select>
                                    </div>
                                    <div class="ops-field ops-field-wide">
                                        <label>Service Days (for weekly profiles)</label>
                                        <div class="feature-checklist">
                                            <label class="feature-item"><input type="checkbox" name="service_days[]" value="1"> Mon</label>
                                            <label class="feature-item"><input type="checkbox" name="service_days[]" value="2"> Tue</label>
                                            <label class="feature-item"><input type="checkbox" name="service_days[]" value="3"> Wed</label>
                                            <label class="feature-item"><input type="checkbox" name="service_days[]" value="4"> Thu</label>
                                            <label class="feature-item"><input type="checkbox" name="service_days[]" value="5"> Fri</label>
                                            <label class="feature-item"><input type="checkbox" name="service_days[]" value="6"> Sat</label>
                                            <label class="feature-item"><input type="checkbox" name="service_days[]" value="0"> Sun</label>
                                        </div>
                                    </div>
                                    <div class="ops-field">
                                        <label for="availability_route_name_{{ $categorySlug }}">Route Name (ferry/speedboat)</label>
                                        <input id="availability_route_name_{{ $categorySlug }}" name="route_name" class="ops-input" type="text" maxlength="120" placeholder="e.g. Male -> Maafushi 07:30" data-availability-role="route">
                                    </div>
                                    <div class="ops-field">
                                        <label for="availability_inventory_{{ $categorySlug }}">Inventory</label>
                                        <input id="availability_inventory_{{ $categorySlug }}" name="inventory" class="ops-input" type="number" min="0" max="100000" required>
                                    </div>
                                    <div class="ops-field">
                                        <label for="availability_closed_{{ $categorySlug }}">Closed Day</label>
                                        <select id="availability_closed_{{ $categorySlug }}" name="is_closed" class="ops-select">
                                            <option value="0">Open</option>
                                            <option value="1">Closed</option>
                                        </select>
                                    </div>
                                    <div class="ops-field ops-field-wide">
                                        <label for="availability_notes_{{ $categorySlug }}">Notes</label>
                                        <textarea id="availability_notes_{{ $categorySlug }}" name="notes" class="ops-textarea" maxlength="2000"></textarea>
                                    </div>
                                </div>
                                <button class="btn btn-primary" type="submit">Save {{ $labelForCategory($categoryKey) }} Availability</button>
                            </form>

                            @if (in_array($categoryKey, ['accommodation', 'remote_workspace'], true))
                                <form class="ops-form" method="POST" action="/portal/vendor/transfer/rates/save" data-transfer-rate-form="{{ $categoryKey }}">
                                    @csrf
                                    <p class="label">Transfer Rate Updates (Availability + Bookings)</p>
                                    <div class="ops-form-grid">
                                        <div class="ops-field ops-field-wide">
                                            <label for="transfer_rate_property_{{ $categorySlug }}">Property / Workspace Listing</label>
                                            <select id="transfer_rate_property_{{ $categorySlug }}" name="vendor_property_id" class="ops-select" data-transfer-rate-target required>
                                                <option value="">Select listing</option>
                                                @foreach ($categoryTransferRateTargets as $transferTarget)
                                                    @php
                                                        $configuredTransferOptions = collect(is_array($transferTarget['transfer_options'] ?? null) ? $transferTarget['transfer_options'] : [])
                                                            ->map(static fn ($item): string => strtolower(trim((string) $item)))
                                                            ->filter(static fn (string $item): bool => $item !== '')
                                                            ->values()
                                                            ->all();
                                                        $configuredTransferRates = is_array($transferTarget['transfer_rates'] ?? null)
                                                            ? $transferTarget['transfer_rates']
                                                            : [];
                                                    @endphp
                                                    <option
                                                        value="{{ (int) ($transferTarget['id'] ?? 0) }}"
                                                        data-transfer-options="{{ implode(',', $configuredTransferOptions) }}"
                                                        @foreach ($transferOptionLabels as $transferKey => $transferLabel)
                                                            data-transfer-rate-{{ $transferKey }}="{{ isset($configuredTransferRates[$transferKey]) && is_numeric($configuredTransferRates[$transferKey]) ? (string) ((float) $configuredTransferRates[$transferKey]) : '' }}"
                                                        @endforeach
                                                    >{{ (string) ($transferTarget['label'] ?? ('Property #' . (int) ($transferTarget['id'] ?? 0))) }}</option>
                                                @endforeach
                                            </select>
                                            <p class="small">Only selected transfer options are updated. Keep base transfer options managed at listing setup.</p>
                                        </div>
                                        @foreach ($transferOptionLabels as $transferKey => $transferLabel)
                                            <div class="ops-field">
                                                <label class="feature-item" style="margin-bottom:6px; display:flex; align-items:center; gap:8px;">
                                                    <input type="checkbox" name="transfer_options[]" value="{{ $transferKey }}" data-transfer-option-check>
                                                    {{ $transferLabel }}
                                                </label>
                                                <input
                                                    name="transfer_rates[{{ $transferKey }}]"
                                                    class="ops-input"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    data-transfer-rate-input="{{ $transferKey }}"
                                                    placeholder="Rate per pax (MVR)"
                                                >
                                            </div>
                                        @endforeach
                                    </div>
                                    <button class="btn btn-secondary" type="submit">Update Transfer Rates</button>
                                </form>
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
                                                <td colspan="6" class="ops-empty">No availability slots for {{ strtolower($labelForCategory($categoryKey)) }} yet.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <p class="ops-subtitle" style="margin-top:12px;">{{ $labelForCategory($categoryKey) }} Reservations</p>
                        <div class="billing-ledger-grid" style="margin-bottom:10px;">
                            <article class="billing-ledger-card">
                                <p class="metric-label">Pending</p>
                                <p class="metric-value">{{ $reservationPendingCount }}</p>
                            </article>
                            <article class="billing-ledger-card">
                                <p class="metric-label">Confirmed</p>
                                <p class="metric-value">{{ $reservationConfirmedCount }}</p>
                            </article>
                            <article class="billing-ledger-card">
                                <p class="metric-label">Completed</p>
                                <p class="metric-value">{{ $reservationCompletedCount }}</p>
                            </article>
                            <article class="billing-ledger-card">
                                <p class="metric-label">Cancelled</p>
                                <p class="metric-value">{{ $reservationCancelledCount }}</p>
                            </article>
                            <article class="billing-ledger-card">
                                <p class="metric-label">Booked Revenue</p>
                                <p class="metric-value">MVR {{ number_format($reservationRevenueTotal, 2) }}</p>
                            </article>
                        </div>

                        <div class="ops-table-wrap">
                            <table class="ops-table" aria-label="{{ $labelForCategory($categoryKey) }} reservations table">
                                <thead>
                                    <tr>
                                        <th>Target</th>
                                        <th>Customer</th>
                                        <th>Dates</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($categoryReservations->take(30) as $reservationRow)
                                        <tr>
                                            <td>{{ (string) ($reservationRow['target_label'] ?? 'Global / Unlinked') }}</td>
                                            <td>
                                                {{ (string) ($reservationRow['customer_name'] ?? '') }}<br>
                                                {{ (string) ($reservationRow['customer_email'] ?? '') }}
                                            </td>
                                            <td>{{ (string) ($reservationRow['start_at'] ?? '-') }}<br>{{ (string) ($reservationRow['end_at'] ?? '-') }}</td>
                                            <td>
                                                @if (is_array($reservationRow['room_pricing'] ?? null))
                                                    @php $rp = $reservationRow['room_pricing']; @endphp
                                                    Room Pricing: {{ (string) ($reservationRow['currency'] ?? 'MVR') }} {{ number_format((float) ($rp['nightly_subtotal'] ?? 0), 2) }} x {{ (int) ($rp['nights'] ?? 1) }} nights<br>
                                                @endif
                                                Base: {{ (string) ($reservationRow['currency'] ?? 'MVR') }} {{ number_format((float) ($reservationRow['subtotal_amount'] ?? 0), 2) }}<br>
                                                Service Charge: {{ (string) ($reservationRow['currency'] ?? 'MVR') }} {{ number_format((float) ($reservationRow['service_charge_total'] ?? 0), 2) }}<br>
                                                Taxes: {{ (string) ($reservationRow['currency'] ?? 'MVR') }} {{ number_format((float) ($reservationRow['total_tax_amount'] ?? 0), 2) }}<br>
                                                Total: {{ (string) ($reservationRow['currency'] ?? 'MVR') }} {{ number_format((float) ($reservationRow['invoice_total_amount'] ?? 0), 2) }}
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
                                                        <option value="paid" @selected(($reservationRow['payment_status'] ?? '') === 'paid')>Paid</option>
                                                        <option value="refunded" @selected(($reservationRow['payment_status'] ?? '') === 'refunded')>Refunded</option>
                                                    </select>
                                                    <button class="btn btn-secondary" type="submit">Update</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="ops-empty">No reservations for {{ strtolower($labelForCategory($categoryKey)) }} yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>