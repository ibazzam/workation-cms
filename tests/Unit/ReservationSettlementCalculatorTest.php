<?php

namespace Tests\Unit;

use App\Support\ReservationSettlementCalculator;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReservationSettlementCalculatorTest extends TestCase
{
    public function test_stripe_fee_and_commission_are_applied(): void
    {
        $result = ReservationSettlementCalculator::calculate(100.00, 'stripe', 'stripe');

        $this->assertSame(12.0, (float) $result['commission_rate_percent']);
        $this->assertSame(6.5, (float) $result['gateway_fee_rate_percent']);
        $this->assertSame(12.0, (float) $result['commission_amount']);
        $this->assertSame(6.5, (float) $result['gateway_fee_amount']);
        $this->assertSame(81.5, (float) $result['vendor_payout_amount']);
    }

    public function test_mib_fee_is_used_for_mib_provider(): void
    {
        $result = ReservationSettlementCalculator::calculate(150.00, 'mib_usd', 'mib');

        $this->assertSame(4.0, (float) $result['gateway_fee_rate_percent']);
        $this->assertSame(6.0, (float) $result['gateway_fee_amount']);
    }

    public function test_bml_and_mib_use_five_to_seven_business_day_settlement_window(): void
    {
        $window = ReservationSettlementCalculator::payoutSettlementWindow('bml_mvr', 'bml');

        $this->assertSame(5, (int) $window['min_business_days']);
        $this->assertSame(7, (int) $window['max_business_days']);
        $this->assertSame('T+5 to T+7 business days', (string) $window['label']);
    }

    public function test_stripe_uses_ten_to_twelve_business_day_settlement_window(): void
    {
        $window = ReservationSettlementCalculator::payoutSettlementWindow('stripe', 'stripe');

        $this->assertSame(10, (int) $window['min_business_days']);
        $this->assertSame(12, (int) $window['max_business_days']);
        $this->assertSame('T+10 to T+12 business days', (string) $window['label']);
    }

    public function test_expected_payout_at_uses_latest_business_day_in_window(): void
    {
        $collectedAt = Carbon::parse('2026-04-01 10:30:00');

        $bmlExpected = ReservationSettlementCalculator::expectedPayoutAt($collectedAt, 'bml_mvr', 'bml');
        $stripeExpected = ReservationSettlementCalculator::expectedPayoutAt($collectedAt, 'stripe', 'stripe');

        $this->assertSame('2026-04-10', $bmlExpected?->toDateString());
        $this->assertSame('2026-04-17', $stripeExpected?->toDateString());
    }
}
