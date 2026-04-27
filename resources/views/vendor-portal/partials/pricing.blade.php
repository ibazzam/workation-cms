<section id="vendorPricingSection" class="card ops-section" aria-label="Vendor pricing rules" data-panel-group="reservations">
            <div class="ops-header">
                <p class="ops-title">Pricing Rules</p>
                <span class="ops-chip">{{ $vendorPricingRules->count() }} active + historical</span>
            </div>
            <p class="wizard-note" style="margin:0 0 10px;">
                Enter round, all-inclusive sell prices (room/service + taxes + service charge + Workation commission + gateway fee).
                Workation commission is 12%. Gateway fee policy: MIB/BML 4%, Stripe 6.5%. Taxes are shown to guests as included values, not added again.
            </p>
            @php
                $allowedCategorySet = collect($vendorAllowedCategoryKeys ?? $selectedVendorCategories ?? [])
                    ->map(static function ($categoryKey) {
                        return vendorPortalCanonicalCategory((string) $categoryKey);
                    })
                    ->filter(static fn ($categoryKey) => is_string($categoryKey) && $categoryKey !== '')
                    ->unique()
                    ->values();

                $filteredVendorProperties = $vendorProperties->filter(static function ($property) use ($allowedCategorySet): bool {
                    $category = vendorPortalCanonicalCategory((string) ($property->listing_category ?? ''));
                    return is_string($category) && $category !== '' && $allowedCategorySet->contains($category);
                })->values();

                $filteredVendorServices = $vendorServices->filter(static function ($service) use ($allowedCategorySet): bool {
                    $category = vendorPortalCanonicalCategory((string) ($service->listing_category ?? ''));
                    return is_string($category) && $category !== '' && $allowedCategorySet->contains($category);
                })->values();

                $transferOptionLabels = [
                    'car' => 'Car',
                    'van' => 'Van',
                    'ferry' => 'Ferry',
                    'speedboat' => 'SpeedBoat',
                    'seaplane' => 'SeaPlane',
                    'domestic_flight' => 'Domestic Flight',
                ];

                $transferRateTargets = $filteredVendorProperties
                    ->filter(static function ($property): bool {
                        $category = vendorPortalCanonicalCategory((string) ($property->listing_category ?? ''));
                        return in_array($category, ['accommodation', 'remote_workspace'], true);
                    })
                    ->map(static function ($property): array {
                        $details = [];
                        if (is_string($property->listing_details ?? null) && trim((string) $property->listing_details) !== '') {
                            $decoded = json_decode((string) $property->listing_details, true);
                            if (is_array($decoded)) {
                                $details = $decoded;
                            }
                        }

                        $configuredTransferOptions = collect(is_array($details['transfer_options'] ?? null) ? $details['transfer_options'] : [])
                            ->map(static fn ($item): string => strtolower(trim((string) $item)))
                            ->filter(static fn (string $item): bool => $item !== '')
                            ->values()
                            ->all();

                        return [
                            'id' => (int) ($property->id ?? 0),
                            'label' => 'Property #' . (int) ($property->id ?? 0) . ' - ' . (string) ($property->name ?? ('Property ' . (int) ($property->id ?? 0))),
                            'transfer_options' => $configuredTransferOptions,
                            'transfer_rates' => is_array($details['transfer_rates'] ?? null) ? $details['transfer_rates'] : [],
                        ];
                    })
                    ->values();

                $propertyCategoryById = $filteredVendorProperties
                    ->mapWithKeys(static function ($property): array {
                        return [
                            (int) ($property->id ?? 0) => vendorPortalCanonicalCategory((string) ($property->listing_category ?? '')),
                        ];
                    });

                $filteredVendorRooms = $vendorRooms->filter(static function ($room) use ($propertyCategoryById, $allowedCategorySet): bool {
                    $propertyId = (int) ($room->vendor_property_id ?? 0);
                    $roomCategory = $propertyCategoryById->get($propertyId);
                    if (!is_string($roomCategory) || $roomCategory === '') {
                        $roomCategory = 'accommodation';
                    }

                    return $allowedCategorySet->contains($roomCategory);
                })->values();
                $filteredPropertyIds = $filteredVendorProperties->pluck('id')->map(static fn ($id) => (int) $id)->filter(static fn ($id) => $id > 0)->values();
                $filteredServiceIds = $filteredVendorServices->pluck('id')->map(static fn ($id) => (int) $id)->filter(static fn ($id) => $id > 0)->values();
                $filteredRoomIds = $filteredVendorRooms->pluck('id')->map(static fn ($id) => (int) $id)->filter(static fn ($id) => $id > 0)->values();
                $hasPricingAccess = $allowedCategorySet->isNotEmpty();
                $filteredPricingRules = $vendorPricingRules->filter(static function ($rule) use ($filteredPropertyIds, $filteredServiceIds, $filteredRoomIds, $hasPricingAccess): bool {
                    if (!$hasPricingAccess) {
                        return false;
                    }

                    $propertyId = (int) ($rule->vendor_property_id ?? 0);
                    $serviceId = (int) ($rule->vendor_service_id ?? 0);
                    $roomId = (int) ($rule->vendor_room_category_id ?? 0);

                    if ($propertyId > 0) {
                        return $filteredPropertyIds->contains($propertyId);
                    }
                    if ($serviceId > 0) {
                        return $filteredServiceIds->contains($serviceId);
                    }
                    if ($roomId > 0) {
                        return $filteredRoomIds->contains($roomId);
                    }

                    return true;
                })->values();

                $recentReservationRows = $vendorReservations->filter(function ($reservation) {
                    $startAt = (string) ($reservation->start_at ?? $reservation->created_at ?? '');
                    $ts = strtotime($startAt);
                    return $ts !== false && $ts >= strtotime('-30 days');
                });

                $propertyDemandMap = $recentReservationRows
                    ->groupBy(static fn ($reservation) => (int) ($reservation->vendor_property_id ?? 0))
                    ->map(static fn ($rows) => $rows->count());

                $serviceDemandMap = $recentReservationRows
                    ->groupBy(static fn ($reservation) => (int) ($reservation->vendor_service_id ?? 0))
                    ->map(static fn ($rows) => $rows->count());

                $roomDemandMap = $recentReservationRows
                    ->groupBy(static fn ($reservation) => (int) ($reservation->vendor_room_category_id ?? 0))
                    ->map(static fn ($rows) => $rows->count());

                $buildPricingSuggestion = static function (
                    string $targetType,
                    int $targetId,
                    string $targetLabel,
                    float $basePrice,
                    int $recentBookings,
                    string $currency = 'MVR'
                ): ?array {
                    if ($targetId <= 0 || $basePrice <= 0) {
                        return null;
                    }

                    $ruleType = 'weekend_markup';
                    $ruleValue = 10.0;
                    $reason = 'Steady demand - apply weekend uplift.';

                    if ($recentBookings <= 2) {
                        $ruleType = 'promo_discount';
                        $ruleValue = 12.0;
                        $reason = 'Low recent demand - run promotional discount.';
                    } elseif ($recentBookings <= 5) {
                        $ruleType = 'demand_discount';
                        $ruleValue = 8.0;
                        $reason = 'Demand softening - apply tactical discount.';
                    } elseif ($recentBookings >= 10) {
                        $ruleType = 'weekend_markup';
                        $ruleValue = 15.0;
                        $reason = 'Strong demand - increase weekend pricing.';
                    }

                    $suggestedPrice = $ruleType === 'weekend_markup'
                        ? round($basePrice * (1 + ($ruleValue / 100)), 2)
                        : round($basePrice * (1 - ($ruleValue / 100)), 2);

                    return [
                        'target_type' => $targetType,
                        'target_id' => $targetId,
                        'target_label' => $targetLabel,
                        'base_price' => round($basePrice, 2),
                        'recent_bookings' => $recentBookings,
                        'rule_type' => $ruleType,
                        'rule_value' => $ruleValue,
                        'suggested_price' => $suggestedPrice,
                        'currency' => $currency,
                        'reason' => $reason,
                    ];
                };

                $pricingSuggestions = collect();

                foreach ($filteredVendorProperties as $property) {
                    $propertyId = (int) ($property->id ?? 0);
                    $basePrice = (float) ($property->base_price ?? 0);
                    $bookings = (int) ($propertyDemandMap->get($propertyId, 0));
                    $suggestion = $buildPricingSuggestion('property', $propertyId, (string) ($property->name ?? ('Property ' . $propertyId)), $basePrice, $bookings, (string) ($property->currency ?? 'MVR'));
                    if (is_array($suggestion)) {
                        $pricingSuggestions->push($suggestion);
                    }
                }

                foreach ($filteredVendorServices as $service) {
                    $serviceId = (int) ($service->id ?? 0);
                    $basePrice = (float) ($service->price ?? 0);
                    $bookings = (int) ($serviceDemandMap->get($serviceId, 0));
                    $suggestion = $buildPricingSuggestion('service', $serviceId, (string) ($service->name ?? ('Service ' . $serviceId)), $basePrice, $bookings, (string) ($service->currency ?? 'MVR'));
                    if (is_array($suggestion)) {
                        $pricingSuggestions->push($suggestion);
                    }
                }

                foreach ($filteredVendorRooms as $room) {
                    $roomId = (int) ($room->id ?? 0);
                    $basePrice = (float) ($room->base_price ?? 0);
                    $bookings = (int) ($roomDemandMap->get($roomId, 0));
                    $suggestion = $buildPricingSuggestion('room', $roomId, (string) ($room->name ?? ('Room ' . $roomId)), $basePrice, $bookings, (string) ($room->currency ?? 'MVR'));
                    if (is_array($suggestion)) {
                        $pricingSuggestions->push($suggestion);
                    }
                }

                $pricingSuggestions = $pricingSuggestions
                    ->sortByDesc('recent_bookings')
                    ->take(30)
                    ->values();
            @endphp
            @if (!$hasPricingAccess)
                <p class="wizard-note" style="margin-bottom:10px;">Pricing and tariff controls are hidden until at least one category is approved by admin.</p>
            @endif
            <div class="ops-grid">
                <form class="ops-form" method="POST" action="/portal/vendor/pricing/create" @if (!$hasPricingAccess) hidden @endif>
                    @csrf
                    <div class="ops-form-grid">
                        <div class="ops-field">
                            <label for="pricing_name">Rule Name</label>
                            <input id="pricing_name" name="name" class="ops-input" type="text" maxlength="160" required>
                        </div>
                        <div class="ops-field">
                            <label for="pricing_type">Rule Type</label>
                            <select id="pricing_type" name="rule_type" class="ops-select" required>
                                <option value="flat">Flat</option>
                                <option value="percent">Percent</option>
                                <option value="nightly">Nightly</option>
                                <option value="weekend_markup">Weekend Markup</option>
                                <option value="demand_discount">Demand Discount</option>
                                <option value="promo_discount">Promo Discount</option>
                            </select>
                        </div>
                        <div class="ops-field">
                            <label for="pricing_value">Value</label>
                            <input id="pricing_value" name="value" class="ops-input" type="number" min="0" step="0.01" required>
                        </div>
                        <div class="ops-field">
                            <label for="pricing_starts">Starts On</label>
                            <input id="pricing_starts" name="starts_on" class="ops-input" type="date">
                        </div>
                        <div class="ops-field">
                            <label for="pricing_ends">Ends On</label>
                            <input id="pricing_ends" name="ends_on" class="ops-input" type="date">
                        </div>
                        <div class="ops-field">
                            <label for="pricing_property_id">Property ID (optional)</label>
                            <input id="pricing_property_id" name="vendor_property_id" class="ops-input" type="number" min="1">
                        </div>
                        <div class="ops-field">
                            <label for="pricing_service_id">Service ID (optional)</label>
                            <input id="pricing_service_id" name="vendor_service_id" class="ops-input" type="number" min="1">
                        </div>
                        <div class="ops-field">
                            <label for="pricing_room_id">Room Category ID (optional)</label>
                            <input id="pricing_room_id" name="vendor_room_category_id" class="ops-input" type="number" min="1">
                        </div>
                    </div>
                    <p class="standards-note">Use Weekend Markup for peak days. Use Demand/Promo Discount when reservations soften to auto-run promotions across properties, services, and rooms.</p>
                    <button class="btn btn-primary" type="submit">Create Pricing Rule</button>
                </form>

                @if ($transferRateTargets->isNotEmpty())
                    <form class="ops-form" method="POST" action="/portal/vendor/transfer/rates/save" data-transfer-rate-form="pricing" @if (!$hasPricingAccess) hidden @endif>
                        @csrf
                        <p class="label">Property-Level Transfer Tariffs</p>
                        <div class="ops-form-grid">
                            <div class="ops-field ops-field-wide">
                                <label for="transfer_rate_property_pricing">Property / Workspace Listing</label>
                                <select id="transfer_rate_property_pricing" name="vendor_property_id" class="ops-select" data-transfer-rate-target required>
                                    <option value="">Select listing</option>
                                    @foreach ($transferRateTargets as $transferTarget)
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
                                <p class="small">Transfer rates are property-level. Room-specific pricing, seasonal rates, weekends, and promotions should be managed with pricing rules.</p>
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

                <div class="ops-table-wrap">
                    <table class="ops-table" aria-label="Auto price suggestions table">
                        <thead>
                            <tr>
                                <th>Target</th>
                                <th>30d Demand</th>
                                <th>Current Price</th>
                                <th>Suggested Rule</th>
                                <th>Suggested Price</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pricingSuggestions as $suggestion)
                                <tr>
                                    <td>
                                        {{ strtoupper((string) ($suggestion['target_type'] ?? '')) }} #{{ (int) ($suggestion['target_id'] ?? 0) }}<br>
                                        {{ (string) ($suggestion['target_label'] ?? 'N/A') }}
                                    </td>
                                    <td>{{ (int) ($suggestion['recent_bookings'] ?? 0) }} reservations</td>
                                    <td>{{ (string) ($suggestion['currency'] ?? 'MVR') }} {{ number_format((float) ($suggestion['base_price'] ?? 0), 2) }}</td>
                                    <td>{{ strtoupper(str_replace('_', ' ', (string) ($suggestion['rule_type'] ?? ''))) }} {{ number_format((float) ($suggestion['rule_value'] ?? 0), 2) }}%</td>
                                    <td>{{ (string) ($suggestion['currency'] ?? 'MVR') }} {{ number_format((float) ($suggestion['suggested_price'] ?? 0), 2) }}</td>
                                    <td>
                                        <button
                                            class="btn btn-secondary"
                                            type="button"
                                            data-price-suggestion="1"
                                            data-target-type="{{ (string) ($suggestion['target_type'] ?? '') }}"
                                            data-target-id="{{ (int) ($suggestion['target_id'] ?? 0) }}"
                                            data-rule-type="{{ (string) ($suggestion['rule_type'] ?? '') }}"
                                            data-rule-value="{{ (float) ($suggestion['rule_value'] ?? 0) }}"
                                            data-target-label="{{ (string) ($suggestion['target_label'] ?? '') }}"
                                            data-reason="{{ (string) ($suggestion['reason'] ?? '') }}"
                                        >Use Suggestion</button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="ops-empty">No suggestions yet. Publish pricing for listings and process bookings to enable recommendation signals.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="ops-table-wrap">
                    <table class="ops-table" aria-label="Vendor pricing rules table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Target</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Window</th>
                                <th>Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($filteredPricingRules->take(12) as $rule)
                                <tr>
                                    <td>{{ $rule->name }}</td>
                                    <td>
                                        @if (!empty($rule->vendor_property_id))
                                            Property #{{ (int) $rule->vendor_property_id }}
                                        @elseif (!empty($rule->vendor_service_id))
                                            Service #{{ (int) $rule->vendor_service_id }}
                                        @elseif (!empty($rule->vendor_room_category_id ?? null))
                                            Room #{{ (int) ($rule->vendor_room_category_id ?? 0) }}
                                        @else
                                            Global
                                        @endif
                                    </td>
                                    <td>{{ strtoupper((string) $rule->rule_type) }}</td>
                                    <td>{{ number_format((float) $rule->value, 2) }}</td>
                                    <td>{{ $rule->starts_on ?: '-' }} to {{ $rule->ends_on ?: '-' }}</td>
                                    <td>{{ $rule->is_active ? 'YES' : 'NO' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="ops-empty">No pricing rules available for unlocked categories yet. Create a first rule to standardize your commercial policy.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>