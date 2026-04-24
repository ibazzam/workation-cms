<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CheckoutPaymentIntentTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_customer_can_create_mvr_payment_intent(): void
    {
        $reservationId = $this->createReservation('Maldivian', 'local_resident', 'MVR');

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/booking/checkout/' . $reservationId . '/payment-intent', [
                'payment_currency' => 'MVR',
                'payment_provider' => 'mib',
                'primary_nationality' => 'Maldivian',
                'guest_residency' => 'local_resident',
            ]);

        $response->assertRedirectContains('/booking/payment/hosted/' . $reservationId);

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'customer_segment' => 'local_maldivian',
            'payment_currency' => 'MVR',
            'payment_gateway' => 'mib_mvr',
            'commission_amount' => 144.00,
            'gateway_fee_amount' => 48.00,
            'vendor_payout_amount' => 1008.00,
        ]);
    }

    public function test_local_customer_can_create_usd_payment_intent_via_stripe(): void
    {
        $reservationId = $this->createReservation('Maldivian', 'local_resident', 'MVR');

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/booking/checkout/' . $reservationId . '/payment-intent', [
                'payment_provider' => 'stripe',
                'payment_currency' => 'USD',
                'primary_nationality' => 'Maldivian',
                'guest_residency' => 'local_resident',
            ]);

        $response->assertRedirectContains('/booking/payment/hosted/' . $reservationId);

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'customer_segment' => 'local_maldivian',
            'payment_currency' => 'USD',
            'payment_gateway' => 'stripe',
            'commission_amount' => 144.00,
            'gateway_fee_amount' => 78.00,
            'vendor_payout_amount' => 978.00,
        ]);
    }

    public function test_foreign_customer_cannot_create_mvr_payment_intent(): void
    {
        $reservationId = $this->createReservation('German', 'foreign_national', 'USD');

        $response = $this
            ->from('/booking/checkout/' . $reservationId)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/booking/checkout/' . $reservationId . '/payment-intent', [
                'payment_currency' => 'MVR',
                'primary_nationality' => 'German',
                'guest_residency' => 'foreign_national',
            ]);

        $response
            ->assertRedirect('/booking/checkout/' . $reservationId)
            ->assertSessionHasErrors(['payment']);
    }

    public function test_payment_intent_requires_primary_nationality_at_checkout(): void
    {
        $reservationId = $this->createReservation('Maldivian', 'local_resident', 'MVR');

        $response = $this
            ->from('/booking/checkout/' . $reservationId)
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/booking/checkout/' . $reservationId . '/payment-intent', [
                'payment_currency' => 'MVR',
                'guest_residency' => 'local_resident',
            ]);

        $response
            ->assertRedirect('/booking/checkout/' . $reservationId)
            ->assertSessionHasErrors(['primary_nationality']);
    }

    private function createReservation(string $nationality, string $guestResidency, string $currency): int
    {
        $vendor = User::factory()->create();

        $propertyId = (int) DB::table('vendor_properties')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'name' => 'Checkout Test Property',
            'property_type' => 'property',
            'location' => 'Male',
            'status' => 'active',
            'base_price' => 1200,
            'currency' => $currency,
            'max_guests' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'vendor_service_id' => null,
            'customer_name' => 'Test Guest',
            'customer_email' => 'guest@example.com',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
            'guests' => 2,
            'total_amount' => 1200,
            'currency' => $currency,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'notes' => json_encode([
                'primary_nationality' => $nationality,
                'guest_residency' => $guestResidency,
                'invoice_total_amount' => 1200,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}