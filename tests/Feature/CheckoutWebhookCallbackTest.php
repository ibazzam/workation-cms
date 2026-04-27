<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CheckoutWebhookCallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_rejects_invalid_signature(): void
    {
        config(['checkout_payments.gateways.stripe.webhook_secret' => 'test_secret_123']);

        $reservationId = $this->createReservation();
        $payload = [
            'reservation_id' => $reservationId,
            'event_id' => 'evt_invalid',
            'intent_id' => 'payint_invalid',
            'reference' => 'REF-INVALID',
            'status' => 'paid',
        ];

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withHeader('X-Workation-Signature', 'bad-signature')
            ->postJson('/booking/payment/webhooks/stripe', $payload);

        $response
            ->assertStatus(401)
            ->assertJson([
                'ok' => false,
                'message' => 'Invalid signature',
            ]);

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ]);
    }

    public function test_webhook_marks_reservation_paid_when_signature_is_valid(): void
    {
        $secret = 'test_secret_456';
        config(['checkout_payments.gateways.stripe.webhook_secret' => $secret]);

        $reservationId = $this->createReservation();
        $payload = [
            'reservation_id' => $reservationId,
            'event_id' => 'evt_paid_001',
            'intent_id' => 'payint_paid_001',
            'reference' => 'REF-PAID-001',
            'status' => 'paid',
        ];

        $rawPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', (string) $rawPayload, $secret);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->call(
                'POST',
                '/booking/payment/webhooks/stripe',
                [],
                [],
                [],
                [
                    'CONTENT_TYPE' => 'application/json',
                    'HTTP_X-Workation-Signature' => $signature,
                ],
                (string) $rawPayload
            );

        $response
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'result' => 'paid',
            ]);

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'payment_status' => 'paid',
            'status' => 'confirmed',
            'payment_reference' => 'REF-PAID-001',
            'payment_intent_id' => 'payint_paid_001',
            'payment_webhook_event_id' => 'evt_paid_001',
            'payment_error' => null,
        ]);
    }

    private function createReservation(): int
    {
        $vendor = User::factory()->create();

        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'name' => 'Webhook Test Property',
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
            'customer_name' => 'Webhook Guest',
            'customer_email' => 'webhook@example.com',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
            'guests' => 2,
            'total_amount' => 1400,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_reference' => null,
            'payment_intent_id' => null,
            'payment_webhook_event_id' => null,
            'notes' => json_encode([
                'primary_nationality' => 'German',
                'guest_residency' => 'foreign_national',
                'invoice_total_amount' => 1400,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
