<?php

namespace Tests\Unit;

use App\Support\CheckoutPaymentRouter;
use InvalidArgumentException;
use Tests\TestCase;

class CheckoutPaymentRouterTest extends TestCase
{
    public function test_local_maldivian_defaults_to_mvr_bank_gateway(): void
    {
        $policy = CheckoutPaymentRouter::buildPaymentPolicy([
            'primary_nationality' => 'Maldivian',
            'guest_residency' => 'local_resident',
            'reservation_currency' => 'MVR',
        ]);

        $this->assertSame('local_maldivian', $policy['segment']);
        $this->assertSame(['MVR', 'USD'], $policy['allowed_currencies']);
        $this->assertSame('MVR', $policy['currency']);
        $this->assertSame('mib_mvr', $policy['gateway']);
    }

    public function test_foreign_guest_is_restricted_to_usd(): void
    {
        $policy = CheckoutPaymentRouter::buildPaymentPolicy([
            'primary_nationality' => 'German',
            'guest_residency' => 'foreign_national',
            'reservation_currency' => 'USD',
        ]);

        $this->assertSame('foreign_national', $policy['segment']);
        $this->assertSame(['USD'], $policy['allowed_currencies']);
        $this->assertSame('USD', $policy['currency']);
        $this->assertSame('stripe', $policy['gateway']);
    }

    public function test_local_guest_can_request_usd_via_stripe(): void
    {
        $policy = CheckoutPaymentRouter::buildPaymentPolicy([
            'primary_nationality' => 'Maldivian',
            'guest_residency' => 'local_resident',
            'reservation_currency' => 'MVR',
        ], 'USD');

        $this->assertSame('USD', $policy['currency']);
        $this->assertSame('stripe', $policy['gateway']);
    }

    public function test_local_guest_cannot_use_bank_usd_gateway(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CheckoutPaymentRouter::buildPaymentPolicy([
            'primary_nationality' => 'Maldivian',
            'guest_residency' => 'local_resident',
            'reservation_currency' => 'MVR',
            'requested_gateway' => 'mib_usd',
        ], 'USD');
    }

    public function test_foreign_provider_selection_maps_to_usd_bank_api(): void
    {
        $policy = CheckoutPaymentRouter::buildPaymentPolicy([
            'primary_nationality' => 'German',
            'guest_residency' => 'foreign_national',
            'reservation_currency' => 'USD',
            'requested_gateway' => 'mib',
        ], 'USD');

        $this->assertSame('mib_usd', $policy['gateway']);
        $this->assertSame('mib', $policy['provider']);
        $this->assertSame('USD', $policy['currency']);
    }

    public function test_foreign_guest_cannot_request_mvr(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CheckoutPaymentRouter::buildPaymentPolicy([
            'primary_nationality' => 'Italian',
            'guest_residency' => 'foreign_national',
            'reservation_currency' => 'USD',
        ], 'MVR');
    }
}