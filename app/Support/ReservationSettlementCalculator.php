<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReservationSettlementCalculator
{
    public static function calculate(float $grossAmount, string $gateway, ?string $provider = null): array
    {
        $providerKey = strtolower(trim((string) $provider));
        if ($providerKey === '') {
            $providerKey = strtolower(trim((string) config('checkout_payments.gateways.' . $gateway . '.provider', '')));
        }

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
}
