<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

    public function test_webhook_marks_reservation_cancelled_when_gateway_reports_cancellation(): void
    {
        $secret = 'test_secret_789';
        config(['checkout_payments.gateways.stripe.webhook_secret' => $secret]);

        $reservationId = $this->createReservation();
        DB::table('vendor_reservations')->where('id', $reservationId)->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);

        $payload = [
            'reservation_id' => $reservationId,
            'event_id' => 'evt_cancelled_001',
            'intent_id' => 'payint_cancelled_001',
            'reference' => 'REF-CANCEL-001',
            'status' => 'cancelled',
            'error' => 'Customer cancelled checkout.',
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
                'result' => 'unpaid',
            ]);

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'payment_status' => 'unpaid',
            'status' => 'cancelled',
            'payment_webhook_event_id' => 'evt_cancelled_001',
            'payment_error' => 'Customer cancelled checkout.',
        ]);
    }

    public function test_webhook_confirmed_status_is_treated_as_paid(): void
    {
        $secret = 'test_secret_confirmed';
        config(['checkout_payments.gateways.stripe.webhook_secret' => $secret]);

        $reservationId = $this->createReservation();
        $payload = [
            'reservation_id' => $reservationId,
            'event_id' => 'evt_confirmed_001',
            'intent_id' => 'payint_confirmed_001',
            'reference' => 'REF-CONFIRMED-001',
            'status' => 'confirmed',
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
            'payment_webhook_event_id' => 'evt_confirmed_001',
        ]);
    }

    public function test_bank_gateway_browser_return_redirects_to_customer_portal(): void
    {
        $reservationId = $this->createReservation();

        $response = $this->get('/booking/payment/webhooks/bml?reservation_id=' . $reservationId);

        $response
            ->assertRedirect(url('/customer?section=bookings&booking=' . $reservationId . '&payment=success'))
            ->assertSessionHas('portal_notice', 'Payment return received. We are verifying your payment status.');
    }

    public function test_bml_browser_return_can_reconcile_confirmed_payment_and_mark_paid(): void
    {
        config([
            'checkout_payments.gateways.bml_mvr.api_key' => 'bml-test-key',
            'checkout_payments.gateways.bml_mvr.mode' => 'sandbox',
        ]);

        Http::fake([
            'https://api.uat.merchants.bankofmaldives.com.mv/public/transactions/*' => Http::response([
                'id' => 'BMLTXN-GET-001',
                'state' => 'CONFIRMED',
            ], 200),
        ]);

        $reservationId = $this->createReservation();
        DB::table('vendor_reservations')->where('id', $reservationId)->update([
            'payment_gateway' => 'bml_mvr',
            'payment_intent_id' => 'payint_bml_001',
            'payment_payload_json' => json_encode(['bml_transaction_id' => 'BMLTXN-GET-001']),
            'updated_at' => now(),
        ]);

        $response = $this->get('/booking/payment/webhooks/bml_mvr?reservation_id=' . $reservationId);

        $response
            ->assertRedirect(url('/customer?section=bookings&booking=' . $reservationId . '&payment=success'))
            ->assertSessionHas('portal_notice', 'Payment verified successfully and your booking is now confirmed.');

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_reference' => 'BMLTXN-GET-001',
            'payment_webhook_event_id' => 'bml_browser_BMLTXN-GET-001',
        ]);
    }

    public function test_bml_browser_return_declined_marks_payment_unpaid_with_error(): void
    {
        $reservationId = $this->createReservation();
        DB::table('vendor_reservations')->where('id', $reservationId)->update([
            'payment_gateway' => 'bml_mvr',
            'payment_intent_id' => 'payint_bml_declined_001',
            'payment_payload_json' => json_encode(['bml_transaction_id' => 'BMLTXN-GET-DECLINED-001']),
            'updated_at' => now(),
        ]);

        $response = $this->get('/booking/payment/webhooks/bml_mvr?reservation_id=' . $reservationId . '&state=DECLINED&transactionId=BMLTXN-GET-DECLINED-001');

        $response
            ->assertRedirect('/customer?section=bookings&booking=' . $reservationId . '&booking_status=awaiting_payment&payment=failed')
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_reference' => 'BMLTXN-GET-DECLINED-001',
            'payment_webhook_event_id' => 'bml_browser_BMLTXN-GET-DECLINED-001',
            'payment_error' => 'BML state: DECLINED',
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