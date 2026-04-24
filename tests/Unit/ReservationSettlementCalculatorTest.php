<?php

namespace Tests\Unit;

use App\Support\ReservationSettlementCalculator;
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
}
