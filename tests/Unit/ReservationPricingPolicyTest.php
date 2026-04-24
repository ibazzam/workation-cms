<?php

namespace Tests\Unit;

use App\Support\ReservationPricingPolicy;
use Tests\TestCase;

class ReservationPricingPolicyTest extends TestCase
{
    public function test_accommodation_foreign_uses_tgst_and_green_tax_not_standard_gst(): void
    {
        $result = ReservationPricingPolicy::calculate([
            'listing_category' => 'accommodation',
            'subtotal_amount' => 100,
            'discount_percent' => 0,
            'adults' => 2,
            'children' => 1,
            'infants' => 0,
            'nights' => 1,
            'room_count' => 60,
            'guest_residency' => 'foreign_national',
        ]);

        $this->assertSame(17.0, (float) $result['tgst_rate_percent']);
        $this->assertSame(17.0, (float) $result['tgst_total']);
        $this->assertSame(0.0, (float) $result['gst_total']);
        $this->assertSame(36.0, (float) $result['green_tax_total']);
        $this->assertSame(10.0, (float) $result['service_charge_total']);
    }

    public function test_non_accommodation_uses_standard_gst_and_service_charge(): void
    {
        $result = ReservationPricingPolicy::calculate([
            'listing_category' => 'restaurant',
            'subtotal_amount' => 100,
            'discount_percent' => 0,
            'adults' => 2,
            'children' => 0,
            'infants' => 0,
            'nights' => 1,
            'room_count' => 0,
            'guest_residency' => 'local_resident',
        ]);

        $this->assertSame(8.0, (float) $result['gst_rate_percent']);
        $this->assertSame(8.0, (float) $result['gst_total']);
        $this->assertSame(0.0, (float) $result['tgst_total']);
        $this->assertSame(0.0, (float) $result['green_tax_total']);
        $this->assertSame(10.0, (float) $result['service_charge_total']);
    }

    public function test_green_tax_excludes_infants_when_configured(): void
    {
        $result = ReservationPricingPolicy::calculate([
            'listing_category' => 'accommodation',
            'subtotal_amount' => 100,
            'discount_percent' => 0,
            'adults' => 2,
            'children' => 1,
            'infants' => 1,
            'nights' => 1,
            'room_count' => 60,
            'guest_residency' => 'foreign_national',
        ]);

        $this->assertSame(24.0, (float) $result['green_tax_total']);
    }
}
