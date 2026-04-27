<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_to_hosted_payment_completion_confirms_reservation(): void
    {
        $reservationId = $this->createReservation();

        $intentResponse = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/booking/checkout/' . $reservationId . '/payment-intent', [
                'payment_currency' => 'MVR',
                'payment_provider' => 'mib',
                'primary_nationality' => 'Maldivian',
                'guest_residency' => 'local_resident',
            ]);

        $intentResponse->assertRedirectContains('/booking/payment/hosted/' . $reservationId . '?intent=');

        $reservationAfterIntent = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertNotNull($reservationAfterIntent);
        $intentId = trim((string) ($reservationAfterIntent->payment_intent_id ?? ''));
        $this->assertNotSame('', $intentId);

        $hostedPage = $this->get('/booking/payment/hosted/' . $reservationId . '?intent=' . urlencode($intentId));
        $hostedPage->assertOk();

        $completeResponse = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/booking/payment/hosted/' . $reservationId . '/complete', [
                'intent_id' => $intentId,
                'payment_reference' => 'INT-' . $reservationId,
            ]);

        $completeResponse
            ->assertRedirect('/booking/checkout/' . $reservationId)
            ->assertSessionHas('portal_notice', 'Payment recorded and reservation confirmed.');

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'payment_status' => 'paid',
            'status' => 'confirmed',
            'payment_reference' => 'INT-' . $reservationId,
        ]);
    }

    private function createReservation(): int
    {
        $vendor = User::factory()->create();

        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'name' => 'Booking E2E Test Property',
            'location' => 'Male',
            'status' => 'active',
            'max_guests' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vendor_accommodation_listings')->where('id', $propertyId)->update(['vendor_property_id' => $propertyId]);

        return (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'vendor_service_id' => null,
            'customer_name' => 'Flow Guest',
            'customer_email' => 'flow@example.com',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(3),
            'guests' => 2,
            'total_amount' => 1200,
            'currency' => 'MVR',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'notes' => json_encode([
                'primary_nationality' => 'Maldivian',
                'guest_residency' => 'local_resident',
                'invoice_total_amount' => 1200,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
