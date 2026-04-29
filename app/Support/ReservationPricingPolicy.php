<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReservationPricingPolicy
{
    private const POLICY_SETTING_KEY = 'reservation_tax_transfer_policy';

    public static function defaultPolicy(): array
    {
        return [
            'taxable_categories' => [
                'accommodation',
                'marine_transport',
                'land_transport',
                'excursion',
                'remote_workspace',
                'conference_room',
                'resort_day_visit',
                'restaurant',
                'vehicle_rental',
                'water_sports',
            ],
            'green_tax_room_threshold' => 50,
            'transfer_default_local_adult_rate' => 25.0,
            'transfer_default_local_child_rate' => 15.0,
            'transfer_default_foreign_adult_rate' => 35.0,
            'transfer_default_foreign_child_rate' => 20.0,
            'transfer_default_base_local' => 0.0,
            'transfer_default_base_foreign' => 0.0,
            'prices_include_tax' => false,
            'tax_components' => [
                [
                    'code' => 'service_charge',
                    'label' => 'Service Charge',
                    'calculation_mode' => 'percent_subtotal',
                    'default_rate' => 10.0,
                    'applies_to' => 'all',
                    'active' => true,
                    'is_service_charge' => true,
                ],
                [
                    'code' => 'green_tax_under_50',
                    'label' => 'Green Tax',
                    'calculation_mode' => 'per_guest_per_night',
                    'default_rate' => 6.0,
                    'applies_to' => 'all',
                    'applies_to_categories' => ['accommodation'],
                    'exclude_infants' => true,
                    'active' => true,
                    'max_room_count' => 49,
                ],
                [
                    'code' => 'green_tax_50_plus',
                    'label' => 'Green Tax',
                    'calculation_mode' => 'per_guest_per_night',
                    'default_rate' => 12.0,
                    'applies_to' => 'all',
                    'applies_to_categories' => ['accommodation'],
                    'exclude_infants' => true,
                    'active' => true,
                    'min_room_count' => 50,
                ],
                [
                    'code' => 'tgst',
                    'label' => 'Tourism GST (T-GST)',
                    'calculation_mode' => 'percent_subtotal',
                    'default_rate' => 17.0,
                    'applies_to' => 'all',
                    'applies_to_categories' => ['accommodation'],
                    'active' => true,
                ],
                [
                    'code' => 'gst_standard',
                    'label' => 'GST (Standard)',
                    'calculation_mode' => 'percent_subtotal',
                    'default_rate' => 8.0,
                    'applies_to' => 'all',
                    'applies_to_categories' => [
                        'marine_transport',
                        'land_transport',
                        'excursion',
                        'remote_workspace',
                        'conference_room',
                        'resort_day_visit',
                        'restaurant',
                        'vehicle_rental',
                        'water_sports',
                    ],
                    'active' => true,
                ],
            ],
        ];
    }

    public static function loadPolicy(): array
    {
        $defaults = self::defaultPolicy();

        if (!Schema::hasTable('portal_finance_settings')) {
            return $defaults;
        }

        $raw = DB::table('portal_finance_settings')
            ->where('setting_key', self::POLICY_SETTING_KEY)
            ->value('value_json');

        if (!is_string($raw) || trim($raw) === '') {
            return $defaults;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        return self::normalizePolicy($decoded, $defaults);
    }

    public static function normalizePolicy(array $input, ?array $base = null): array
    {
        $policy = $base ?? self::defaultPolicy();

        $numericKeys = [
            'transfer_default_local_adult_rate',
            'transfer_default_local_child_rate',
            'transfer_default_foreign_adult_rate',
            'transfer_default_foreign_child_rate',
            'transfer_default_base_local',
            'transfer_default_base_foreign',
        ];

        foreach ($numericKeys as $key) {
            if (array_key_exists($key, $input) && is_numeric($input[$key])) {
                $policy[$key] = round((float) $input[$key], 4);
            }
        }

        if (array_key_exists('green_tax_room_threshold', $input) && is_numeric($input['green_tax_room_threshold'])) {
            $policy['green_tax_room_threshold'] = max(1, (int) $input['green_tax_room_threshold']);
        }

        if (array_key_exists('prices_include_tax', $input)) {
            $policy['prices_include_tax'] = (bool) $input['prices_include_tax'];
        }

        if (isset($input['taxable_categories']) && is_array($input['taxable_categories'])) {
            $categories = array_values(array_unique(array_filter(array_map(
                static fn ($value): string => strtolower(trim((string) $value)),
                $input['taxable_categories']
            ), static fn (string $value): bool => $value !== '')));

            if ($categories !== []) {
                $policy['taxable_categories'] = $categories;
            }
        }

        if (isset($input['tax_components']) && is_array($input['tax_components'])) {
            $policy['tax_components'] = self::normalizeTaxComponents($input['tax_components']);
        }

        return $policy;
    }

    public static function normalizeTaxComponents(array $components): array
    {
        $normalized = [];

        foreach ($components as $component) {
            if (!is_array($component)) {
                continue;
            }

            $code = strtolower(trim((string) ($component['code'] ?? '')));
            $code = preg_replace('/[^a-z0-9_]+/', '_', $code) ?? $code;
            $code = trim((string) preg_replace('/_+/', '_', $code), '_');
            if ($code === '') {
                continue;
            }

            $mode = strtolower(trim((string) ($component['calculation_mode'] ?? 'percent_subtotal')));
            if (!in_array($mode, ['percent_subtotal', 'per_guest_per_night', 'flat_booking'], true)) {
                $mode = 'percent_subtotal';
            }

            $appliesTo = strtolower(trim((string) ($component['applies_to'] ?? 'all')));
            if (!in_array($appliesTo, ['all', 'local_resident', 'foreign_national'], true)) {
                $appliesTo = 'all';
            }

            $normalized[] = [
                'code' => $code,
                'label' => trim((string) ($component['label'] ?? Str::headline($code))),
                'calculation_mode' => $mode,
                'default_rate' => round(max(0, (float) ($component['default_rate'] ?? 0)), 4),
                'applies_to' => $appliesTo,
                'applies_to_categories' => self::normalizeComponentCategories($component['applies_to_categories'] ?? null),
                'active' => (bool) ($component['active'] ?? true),
                'is_service_charge' => (bool) ($component['is_service_charge'] ?? false),
                'exclude_infants' => (bool) ($component['exclude_infants'] ?? false),
                'min_room_count' => isset($component['min_room_count']) && is_numeric($component['min_room_count'])
                    ? max(0, (int) $component['min_room_count'])
                    : null,
                'max_room_count' => isset($component['max_room_count']) && is_numeric($component['max_room_count'])
                    ? max(0, (int) $component['max_room_count'])
                    : null,
            ];
        }

        return array_values($normalized);
    }

    public static function isForeigner(string $nationality, ?string $residency): bool
    {
        $normalizedResidency = strtolower(trim((string) $residency));
        if ($normalizedResidency === 'local_resident') {
            return false;
        }
        if ($normalizedResidency === 'foreign_national') {
            return true;
        }

        $normalizedNationality = strtolower(trim($nationality));
        $localAliases = ['maldives', 'maldivian', 'mv', 'mvn'];

        return !in_array($normalizedNationality, $localAliases, true);
    }

    public static function calculate(array $payload, ?array $policy = null): array
    {
        $activePolicy = self::normalizePolicy($policy ?? self::loadPolicy());

        $listingCategory = strtolower(trim((string) Arr::get($payload, 'listing_category', 'accommodation')));
        $subtotalAmount = round(max(0, (float) Arr::get($payload, 'subtotal_amount', 0)), 2);
        $discountPercent = max(0, min(100, (float) Arr::get($payload, 'discount_percent', 0)));
        $discountAmount = round($subtotalAmount * ($discountPercent / 100), 2);
        $discountedSubtotal = round(max(0, $subtotalAmount - $discountAmount), 2);

        $adults = max(1, (int) Arr::get($payload, 'adults', 1));
        $children = max(0, (int) Arr::get($payload, 'children', 0));
        $infants = max(0, (int) Arr::get($payload, 'infants', 0));
        $guestCount = max(1, $adults + $children);
        $nights = max(1, (int) Arr::get($payload, 'nights', 1));
        $roomCount = max(0, (int) Arr::get($payload, 'room_count', 0));

        $guestResidency = strtolower(trim((string) Arr::get($payload, 'guest_residency', '')));
        if (!in_array($guestResidency, ['local_resident', 'foreign_national'], true)) {
            $guestResidency = self::isForeigner((string) Arr::get($payload, 'primary_nationality', ''), null)
                ? 'foreign_national'
                : 'local_resident';
        }
        $guestIsForeigner = $guestResidency === 'foreign_national';

        $taxableCategories = Arr::get($activePolicy, 'taxable_categories', ['accommodation']);
        $taxApplies = in_array($listingCategory, $taxableCategories, true);
        $pricesIncludeTax = (bool) Arr::get($payload, 'prices_include_tax', Arr::get($activePolicy, 'prices_include_tax', false));

        $vendorTaxOverridesRaw = Arr::get($payload, 'vendor_tax_overrides', []);
        $vendorTaxOverrides = is_array($vendorTaxOverridesRaw) ? $vendorTaxOverridesRaw : [];
        
        // Property currency for Green Tax FX conversion (Green Tax rates are specified in USD)
        $propertyCurrency = strtoupper(trim((string) Arr::get($payload, 'property_currency', 'MVR')));

        $taxLines = [];
        $serviceChargeRatePercent = 0.0;
        $serviceChargeTotal = 0.0;
        $greenTaxRatePerPersonPerNight = 0.0;
        $greenTaxTotal = 0.0;
        $tgstRatePercent = 0.0;
        $tgstTotal = 0.0;
        $gstRatePercent = 0.0;
        $gstTotal = 0.0;

        $components = self::normalizeTaxComponents((array) Arr::get($activePolicy, 'tax_components', []));
        if ($taxApplies) {
            foreach ($components as $component) {
                if (!($component['active'] ?? true)) {
                    continue;
                }

                if (!self::componentAppliesToResidency((string) ($component['applies_to'] ?? 'all'), $guestResidency)) {
                    continue;
                }

                if (!self::componentAppliesToCategory((array) ($component['applies_to_categories'] ?? []), $listingCategory)) {
                    continue;
                }

                $minRoomCount = $component['min_room_count'] ?? null;
                $maxRoomCount = $component['max_room_count'] ?? null;
                if (is_int($minRoomCount) && $roomCount < $minRoomCount) {
                    continue;
                }
                if (is_int($maxRoomCount) && $roomCount > $maxRoomCount) {
                    continue;
                }

                $code = (string) ($component['code'] ?? '');
                $overrideRate = array_key_exists($code, $vendorTaxOverrides) && is_numeric($vendorTaxOverrides[$code])
                    ? max(0, (float) $vendorTaxOverrides[$code])
                    : null;
                $appliedRate = round($overrideRate ?? (float) ($component['default_rate'] ?? 0), 4);
                $mode = (string) ($component['calculation_mode'] ?? 'percent_subtotal');

                $amount = 0.0;
                if ($mode === 'percent_subtotal') {
                    $amount = round($discountedSubtotal * ($appliedRate / 100), 2);
                } elseif ($mode === 'per_guest_per_night') {
                    $chargeableGuests = $guestCount;
                    if ((bool) ($component['exclude_infants'] ?? false)) {
                        $chargeableGuests = max(0, $chargeableGuests - $infants);
                    }
                    $amount = round($appliedRate * $chargeableGuests * $nights, 2);
                    
                    // Green Tax rates are specified in USD; convert to property currency if needed
                    if (str_contains($code, 'green_tax') && $propertyCurrency !== 'USD') {
                        $fxRates = (array) config('checkout_payments.fx_rates', ['USD' => 15.42, 'MVR' => 1.0]);
                        $usdRate = (float) ($fxRates['USD'] ?? 15.42);
                        $propertyRate = (float) ($fxRates[$propertyCurrency] ?? $usdRate);
                        if ($propertyRate > 0) {
                            $amount = round($amount * $usdRate / $propertyRate, 2);
                        }
                    }
                } elseif ($mode === 'flat_booking') {
                    $amount = round($appliedRate, 2);
                }

                $taxLines[] = [
                    'code' => $code,
                    'label' => (string) ($component['label'] ?? Str::headline($code)),
                    'calculation_mode' => $mode,
                    'rate' => $appliedRate,
                    'amount' => $amount,
                    'is_service_charge' => (bool) ($component['is_service_charge'] ?? false),
                ];

                if ((bool) ($component['is_service_charge'] ?? false)) {
                    $serviceChargeTotal = round($serviceChargeTotal + $amount, 2);
                    if ($mode === 'percent_subtotal' && $serviceChargeRatePercent <= 0) {
                        $serviceChargeRatePercent = $appliedRate;
                    }
                    continue;
                }

                if (str_contains($code, 'green_tax')) {
                    $greenTaxTotal = round($greenTaxTotal + $amount, 2);
                    if ($mode === 'per_guest_per_night' && $greenTaxRatePerPersonPerNight <= 0) {
                        $greenTaxRatePerPersonPerNight = $appliedRate;
                    }
                }
                if (str_contains($code, 'tgst')) {
                    $tgstTotal = round($tgstTotal + $amount, 2);
                    if ($mode === 'percent_subtotal' && $tgstRatePercent <= 0) {
                        $tgstRatePercent = $appliedRate;
                    }
                }
                if (str_contains($code, 'gst') && !str_contains($code, 'tgst')) {
                    $gstTotal = round($gstTotal + $amount, 2);
                    if ($mode === 'percent_subtotal' && $gstRatePercent <= 0) {
                        $gstRatePercent = $appliedRate;
                    }
                }
            }
        }

        if ($taxApplies && $pricesIncludeTax && $taxLines !== []) {
            $fixedIncludedTotal = round(array_sum(array_map(static function (array $line): float {
                $mode = (string) ($line['calculation_mode'] ?? 'percent_subtotal');
                return $mode === 'percent_subtotal' ? 0.0 : (float) ($line['amount'] ?? 0);
            }, $taxLines)), 2);

            $percentTotalRate = round(array_sum(array_map(static function (array $line): float {
                $mode = (string) ($line['calculation_mode'] ?? 'percent_subtotal');
                return $mode === 'percent_subtotal' ? (float) ($line['rate'] ?? 0) : 0.0;
            }, $taxLines)), 4);

            if ($percentTotalRate > 0) {
                $percentBaseGross = max(0, $discountedSubtotal - $fixedIncludedTotal);
                $percentBaseNet = $percentBaseGross / (1 + ($percentTotalRate / 100));

                $taxLines = array_map(static function (array $line) use ($percentBaseNet): array {
                    $mode = (string) ($line['calculation_mode'] ?? 'percent_subtotal');
                    if ($mode !== 'percent_subtotal') {
                        return $line;
                    }

                    $rate = (float) ($line['rate'] ?? 0);
                    $line['amount'] = round($percentBaseNet * ($rate / 100), 2);
                    return $line;
                }, $taxLines);

                $serviceChargeTotal = 0.0;
                $greenTaxRatePerPersonPerNight = 0.0;
                $greenTaxTotal = 0.0;
                $tgstRatePercent = 0.0;
                $tgstTotal = 0.0;
                $gstRatePercent = 0.0;
                $gstTotal = 0.0;

                foreach ($taxLines as $line) {
                    $code = (string) ($line['code'] ?? '');
                    $mode = (string) ($line['calculation_mode'] ?? 'percent_subtotal');
                    $amount = (float) ($line['amount'] ?? 0);
                    $rate = (float) ($line['rate'] ?? 0);

                    if ((bool) ($line['is_service_charge'] ?? false)) {
                        $serviceChargeTotal = round($serviceChargeTotal + $amount, 2);
                        if ($mode === 'percent_subtotal' && $serviceChargeRatePercent <= 0) {
                            $serviceChargeRatePercent = $rate;
                        }
                        continue;
                    }

                    if (str_contains($code, 'green_tax')) {
                        $greenTaxTotal = round($greenTaxTotal + $amount, 2);
                        if ($mode === 'per_guest_per_night' && $greenTaxRatePerPersonPerNight <= 0) {
                            $greenTaxRatePerPersonPerNight = $rate;
                        }
                    }
                    if (str_contains($code, 'tgst')) {
                        $tgstTotal = round($tgstTotal + $amount, 2);
                        if ($mode === 'percent_subtotal' && $tgstRatePercent <= 0) {
                            $tgstRatePercent = $rate;
                        }
                    }
                    if (str_contains($code, 'gst') && !str_contains($code, 'tgst')) {
                        $gstTotal = round($gstTotal + $amount, 2);
                        if ($mode === 'percent_subtotal' && $gstRatePercent <= 0) {
                            $gstRatePercent = $rate;
                        }
                    }
                }
            }
        }

        $totalTaxAmount = round(array_sum(array_map(
            static fn (array $line): float => (bool) ($line['is_service_charge'] ?? false) ? 0.0 : (float) ($line['amount'] ?? 0),
            $taxLines
        )), 2);

        $transferOption = strtolower(trim((string) Arr::get($payload, 'transfer_option', '')));
        $transferConfig = self::resolveTransferCharge(
            transferOption: $transferOption,
            adults: $adults,
            children: $children,
            isForeigner: $guestIsForeigner,
            propertyTransferOptions: Arr::get($payload, 'property_transfer_options', []),
            policy: $activePolicy,
            overrideTotal: Arr::get($payload, 'transfer_charge_override')
        );

        // Apply 8% GST to transfer charges if transfer is selected
        $transferGstAmount = 0.0;
        if ($transferConfig['transfer_charge_total'] > 0 && $transferOption !== '') {
            $transferGstRate = 8.0;
            $transferGstAmount = round($transferConfig['transfer_charge_total'] * ($transferGstRate / 100), 2);
            
            // Add transfer GST to tax lines for display
            $taxLines[] = [
                'code' => 'transfer_gst',
                'label' => 'Transfer GST (8%)',
                'calculation_mode' => 'percent_subtotal',
                'rate' => $transferGstRate,
                'amount' => $transferGstAmount,
                'is_service_charge' => false,
            ];
            
            // Add transfer GST to total tax amount
            $totalTaxAmount = round($totalTaxAmount + $transferGstAmount, 2);
        }

        $invoiceTotalAmount = $pricesIncludeTax
            ? round($discountedSubtotal + $transferConfig['transfer_charge_total'] + $transferGstAmount, 2)
            : round($discountedSubtotal + $serviceChargeTotal + $totalTaxAmount + $transferConfig['transfer_charge_total'] + $transferGstAmount, 2);

        return [
            'listing_category' => $listingCategory,
            'guest_residency' => $guestResidency,
            'guest_is_foreigner' => $guestIsForeigner,
            'adults' => $adults,
            'children' => $children,
            'infants' => $infants,
            'guest_count' => $guestCount,
            'nights' => $nights,
            'room_count' => $roomCount,
            'subtotal_amount' => $subtotalAmount,
            'discount_percent' => round($discountPercent, 2),
            'discount_amount' => $discountAmount,
            'discounted_subtotal' => $discountedSubtotal,
            'service_charge_rate_percent' => round($serviceChargeRatePercent, 2),
            'service_charge_total' => $serviceChargeTotal,
            'green_tax_rate_per_person_per_night' => round($greenTaxRatePerPersonPerNight, 2),
            'green_tax_total' => $greenTaxTotal,
            'tgst_rate_percent' => round($tgstRatePercent, 2),
            'tgst_total' => $tgstTotal,
            'gst_rate_percent' => round($gstRatePercent, 2),
            'gst_total' => $gstTotal,
            'total_tax_amount' => $totalTaxAmount,
            'tax_and_fee_total' => round($serviceChargeTotal + $totalTaxAmount, 2),
            'tax_lines' => $taxLines,
            'prices_include_tax' => $pricesIncludeTax,
            'vendor_tax_overrides' => $vendorTaxOverrides,
            'transfer_option' => $transferConfig['transfer_option'],
            'transfer_option_label' => $transferConfig['transfer_option_label'],
            'transfer_local_adult_rate' => $transferConfig['local_adult_rate'],
            'transfer_local_child_rate' => $transferConfig['local_child_rate'],
            'transfer_foreign_adult_rate' => $transferConfig['foreign_adult_rate'],
            'transfer_foreign_child_rate' => $transferConfig['foreign_child_rate'],
            'transfer_applied_adult_rate' => $transferConfig['applied_adult_rate'],
            'transfer_applied_child_rate' => $transferConfig['applied_child_rate'],
            'transfer_charge_total' => $transferConfig['transfer_charge_total'],
            'transfer_gst_amount' => $transferGstAmount,
            'invoice_total_amount' => $invoiceTotalAmount,
            'policy_snapshot' => $activePolicy,
        ];
    }

    private static function componentAppliesToResidency(string $appliesTo, string $guestResidency): bool
    {
        return match ($appliesTo) {
            'local_resident' => $guestResidency === 'local_resident',
            'foreign_national' => $guestResidency === 'foreign_national',
            default => true,
        };
    }

    private static function normalizeComponentCategories(mixed $categories): array
    {
        if (is_string($categories)) {
            $categories = explode(',', $categories);
        }

        if (!is_array($categories)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(static function ($value): string {
            $normalized = strtolower(trim((string) $value));
            $normalized = str_replace([' ', '-'], '_', $normalized);
            return preg_replace('/[^a-z0-9_]+/', '', $normalized) ?? '';
        }, $categories), static fn (string $value): bool => $value !== '')));
    }

    private static function componentAppliesToCategory(array $componentCategories, string $listingCategory): bool
    {
        if ($componentCategories === []) {
            return true;
        }

        return in_array($listingCategory, $componentCategories, true);
    }

    private static function resolveTransferCharge(
        string $transferOption,
        int $adults,
        int $children,
        bool $isForeigner,
        mixed $propertyTransferOptions,
        array $policy,
        mixed $overrideTotal = null
    ): array {
        $defaultLocalAdultRate = max(0, (float) Arr::get($policy, 'transfer_default_local_adult_rate', 0));
        $defaultLocalChildRate = max(0, (float) Arr::get($policy, 'transfer_default_local_child_rate', 0));
        $defaultForeignAdultRate = max(0, (float) Arr::get($policy, 'transfer_default_foreign_adult_rate', 0));
        $defaultForeignChildRate = max(0, (float) Arr::get($policy, 'transfer_default_foreign_child_rate', 0));
        $defaultLocalBase = max(0, (float) Arr::get($policy, 'transfer_default_base_local', 0));
        $defaultForeignBase = max(0, (float) Arr::get($policy, 'transfer_default_base_foreign', 0));

        $localAdultRate = $defaultLocalAdultRate;
        $localChildRate = $defaultLocalChildRate;
        $foreignAdultRate = $defaultForeignAdultRate;
        $foreignChildRate = $defaultForeignChildRate;
        $baseLocal = $defaultLocalBase;
        $baseForeign = $defaultForeignBase;
        $optionLabel = '';

        $transferOptions = is_array($propertyTransferOptions) ? $propertyTransferOptions : [];
        if ($transferOption !== '' && $transferOptions !== []) {
            foreach ($transferOptions as $option) {
                if (!is_array($option)) {
                    continue;
                }

                $candidateCode = strtolower(trim((string) ($option['code'] ?? '')));
                if ($candidateCode === '' || $candidateCode !== $transferOption) {
                    continue;
                }

                $optionLabel = trim((string) ($option['label'] ?? ''));

                $localAdultRate = self::pickRate($option, ['local_adult_charge', 'adult_charge_local', 'adult_charge'], $defaultLocalAdultRate);
                $localChildRate = self::pickRate($option, ['local_child_charge', 'child_charge_local', 'child_charge'], $defaultLocalChildRate);
                $foreignAdultRate = self::pickRate($option, ['foreign_adult_charge', 'adult_charge_foreign', 'adult_charge'], $defaultForeignAdultRate);
                $foreignChildRate = self::pickRate($option, ['foreign_child_charge', 'child_charge_foreign', 'child_charge'], $defaultForeignChildRate);
                $baseLocal = self::pickRate($option, ['base_charge_local', 'local_base_charge', 'base_charge'], $defaultLocalBase);
                $baseForeign = self::pickRate($option, ['base_charge_foreign', 'foreign_base_charge', 'base_charge'], $defaultForeignBase);
                break;
            }
        }

        $appliedAdultRate = $isForeigner ? $foreignAdultRate : $localAdultRate;
        $appliedChildRate = $isForeigner ? $foreignChildRate : $localChildRate;
        $appliedBase = $isForeigner ? $baseForeign : $baseLocal;

        if (is_numeric($overrideTotal)) {
            $transferTotal = round(max(0, (float) $overrideTotal), 2);
        } else {
            $transferTotal = round($appliedBase + ($appliedAdultRate * max(0, $adults)) + ($appliedChildRate * max(0, $children)), 2);
        }

        return [
            'transfer_option' => $transferOption,
            'transfer_option_label' => $optionLabel,
            'local_adult_rate' => round($localAdultRate, 2),
            'local_child_rate' => round($localChildRate, 2),
            'foreign_adult_rate' => round($foreignAdultRate, 2),
            'foreign_child_rate' => round($foreignChildRate, 2),
            'applied_adult_rate' => round($appliedAdultRate, 2),
            'applied_child_rate' => round($appliedChildRate, 2),
            'transfer_charge_total' => $transferTotal,
        ];
    }

    private static function pickRate(array $option, array $keys, float $fallback): float
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $option) && is_numeric($option[$key])) {
                return max(0, (float) $option[$key]);
            }
        }

        return max(0, $fallback);
    }
}
