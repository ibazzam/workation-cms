<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReservationSettlementCalculator
{
    public static function calculate(float $grossAmount, string $gateway, ?string $provider = null): array
    {
        $providerKey = self::resolveProviderKey($gateway, $provider);

        $commissionRate = self::resolveCommissionRate();
        $gatewayRate = self::resolveGatewayFeeRate($providerKey);

        $commissionAmount = round(max(0, $grossAmount) * ($commissionRate / 100), 2);
        $gatewayFeeAmount = round(max(0, $grossAmount) * ($gatewayRate / 100), 2);
        $vendorPayoutAmount = round(max(0, $grossAmount) - $commissionAmount - $gatewayFeeAmount, 2);

        return [
            'provider' => $providerKey,
            'commission_rate_percent' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'gateway_fee_rate_percent' => $gatewayRate,
            'gateway_fee_amount' => $gatewayFeeAmount,
            'vendor_payout_amount' => $vendorPayoutAmount,
        ];
    }

    public static function resolveProviderKey(string $gateway, ?string $provider = null): string
    {
        $providerKey = strtolower(trim((string) $provider));
        if ($providerKey !== '') {
            return $providerKey;
        }

        return strtolower(trim((string) config('checkout_payments.gateways.' . $gateway . '.provider', '')));
    }

    /**
     * Provider settlement window to Workation's bank account.
     * Vendors are paid only after these funds are expected to have settled.
     *
     * @return array{provider:string,min_business_days:int,max_business_days:int,label:string}
     */
    public static function payoutSettlementWindow(string $gateway, ?string $provider = null): array
    {
        $providerKey = self::resolveProviderKey($gateway, $provider);

        if (in_array($providerKey, ['bml', 'mib'], true)) {
            return [
                'provider' => $providerKey,
                'min_business_days' => 5,
                'max_business_days' => 7,
                'label' => 'T+5 to T+7 business days',
            ];
        }

        return [
            'provider' => $providerKey !== '' ? $providerKey : 'stripe',
            'min_business_days' => 10,
            'max_business_days' => 12,
            'label' => 'T+10 to T+12 business days',
        ];
    }

    public static function expectedPayoutAt(mixed $collectedAt, string $gateway, ?string $provider = null, bool $useMaxWindow = true): ?Carbon
    {
        $baseDate = self::normalizeCarbon($collectedAt);
        if (!$baseDate) {
            return null;
        }

        $window = self::payoutSettlementWindow($gateway, $provider);
        $businessDays = $useMaxWindow
            ? (int) ($window['max_business_days'] ?? 0)
            : (int) ($window['min_business_days'] ?? 0);

        return self::addBusinessDays($baseDate, $businessDays)->endOfDay();
    }

    private static function resolveCommissionRate(): float
    {
        $fallback = round(max(0, (float) config('checkout_payments.commission_rate_percent', 12.0)), 4);
        if (!Schema::hasTable('portal_finance_settings')) {
            return $fallback;
        }

        $stored = DB::table('portal_finance_settings')
            ->where('setting_key', 'commission_rate_percent')
            ->value('value_decimal');

        if (!is_numeric($stored)) {
            return $fallback;
        }

        return round(max(0, (float) $stored), 4);
    }

    private static function resolveGatewayFeeRate(string $provider): float
    {
        $rates = (array) config('checkout_payments.provider_fee_rate_percent', []);
        $rate = $rates[$provider] ?? 0;

        return round(max(0, (float) $rate), 4);
    }

    private static function normalizeCarbon(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy();
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if ($value === null) {
            return null;
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return null;
        }

        return Carbon::parse($stringValue);
    }

    private static function addBusinessDays(Carbon $date, int $days): Carbon
    {
        $cursor = $date->copy();
        $remaining = max(0, $days);

        while ($remaining > 0) {
            $cursor->addDay();
            if ($cursor->isWeekend()) {
                continue;
            }

            $remaining--;
        }

        return $cursor;
    }
}
