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

    public function test_unpaid_reservations_do_not_count_as_booking_overlap(): void
    {
        $reservationId = $this->createReservation();
        $reservation = DB::table('vendor_reservations')->where('id', $reservationId)->first();

        $this->assertNotNull($reservation);

        $overlapCount = workationOverlappingReservationCount(
            (int) ($reservation->vendor_user_id ?? 0),
            (int) ($reservation->vendor_property_id ?? 0),
            now()->addDay()->startOfDay(),
            now()->addDays(3)->startOfDay(),
            null,
            null
        );

        $this->assertSame(0, $overlapCount);

        DB::table('vendor_reservations')->where('id', $reservationId)->update([
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

        $paidOverlapCount = workationOverlappingReservationCount(
            (int) ($reservation->vendor_user_id ?? 0),
            (int) ($reservation->vendor_property_id ?? 0),
            now()->addDay()->startOfDay(),
            now()->addDays(3)->startOfDay(),
            null,
            null
        );

        $this->assertSame(1, $paidOverlapCount);
    }

    public function test_transfer_selection_is_saved_when_option_is_posted_without_checkbox(): void
    {
        $reservationId = $this->createReservation([
            'notes' => [
                'primary_nationality' => 'Maldivian',
                'guest_residency' => 'local_resident',
                'invoice_total_amount' => 1200,
                'subtotal_amount' => 1000,
                'discount_percent' => 0,
                'discounted_subtotal' => 1000,
                'adults' => 2,
                'children' => 1,
                'infants' => 0,
                'nights' => 2,
                'rooms' => 1,
                'category_key' => 'accommodation',
                'property_transfer_options' => [
                    [
                        'code' => 'speedboat',
                        'label' => 'Speedboat',
                        'local_adult_charge' => 50,
                        'local_child_charge' => 25,
                        'foreign_adult_charge' => 80,
                        'foreign_child_charge' => 40,
                        'base_charge_local' => 10,
                        'base_charge_foreign' => 15,
                    ],
                ],
            ],
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/booking/checkout/' . $reservationId . '/transfer', [
                'transfer_option' => 'speedboat',
            ]);

        $response->assertRedirect('/booking/checkout/' . $reservationId);

        $reservation = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertNotNull($reservation);

        $notes = json_decode((string) ($reservation->notes ?? ''), true);
        $this->assertIsArray($notes);
        $this->assertSame('speedboat', (string) ($notes['transfer_option'] ?? ''));
        $this->assertSame('Speedboat', (string) ($notes['transfer_option_label'] ?? ''));
        $this->assertEquals(135.0, (float) ($notes['transfer_charge_total'] ?? 0));
    }

    public function test_service_guest_details_redirects_to_transfer_step_even_without_transfer_options(): void
    {
        $reservationId = $this->createReservation([
            'currency' => 'MVR',
            'notes' => [
                'category_key' => 'excursion',
                'property_transfer_options' => [],
                'quote_payment_currency' => 'USD',
                'quote_payment_amount' => 77.35,
                'quote_source_currency' => 'MVR',
                'quote_source_amount' => 1200,
            ],
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/booking/checkout/' . $reservationId . '/guest-details', [
                'primary_first_name' => 'Guest',
                'primary_last_name' => 'Customer',
                'primary_nationality' => 'Germany',
                'guest_residency' => 'foreign_national',
                'primary_email' => 'guest@example.com',
                'primary_mobile' => '+49123456',
            ]);

        $response->assertRedirect('/booking/checkout/' . $reservationId . '/transfer');

        $transferPage = $this->get('/booking/checkout/' . $reservationId . '/transfer');
        $transferPage->assertOk();
        $transferPage->assertSee('/booking/checkout/' . $reservationId, false);
        $transferPage->assertSee('USD', false);
    }

    public function test_service_checkout_summary_uses_locked_payment_currency(): void
    {
        $reservationId = $this->createReservation([
            'notes' => [
                'category_key' => 'excursion',
                'primary_first_name' => 'Guest',
                'primary_last_name' => 'Customer',
                'primary_email' => 'guest@example.com',
                'primary_mobile' => '+49123456',
                'primary_nationality' => 'Germany',
                'guest_residency' => 'foreign_national',
                'room_subtotal' => 1000,
                'subtotal_amount' => 1000,
                'discount_amount' => 0,
                'discounted_subtotal' => 1000,
                'invoice_total_amount' => 1200,
                'quote_payment_currency' => 'USD',
                'quote_payment_amount' => 77.35,
                'quote_source_currency' => 'MVR',
                'quote_source_amount' => 1200,
            ],
        ]);

        $response = $this->get('/booking/checkout/' . $reservationId);

        $response->assertOk();
        $response->assertSee('Payment Currency', false);
        $response->assertSee('USD', false);
        $response->assertDontSee('MVR 1,200.00', false);
    }

    public function test_checkout_to_hosted_payment_completion_confirms_reservation(): void
    {
        $reservationId = $this->createReservation();

        $intentResponse = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession(['portal_customer_authenticated' => true])
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
            ->assertRedirect(url('/customer?section=bookings&booking=' . $reservationId . '&payment=success'))
            ->assertSessionHas('portal_notice', 'Payment recorded and reservation confirmed.');

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'payment_status' => 'paid',
            'status' => 'confirmed',
            'payment_reference' => 'INT-' . $reservationId,
        ]);
    }

    public function test_paid_checkout_page_redirects_to_customer_portal(): void
    {
        $reservationId = $this->createReservation([
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

        $response = $this->get('/booking/checkout/' . $reservationId);

        $response
            ->assertRedirect(url('/customer?section=bookings&booking=' . $reservationId . '&payment=success'))
            ->assertSessionHas('portal_notice', 'Payment already completed. Your booking is available in the customer portal.');
    }

    private function createReservation(array $overrides = []): int
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

        $defaultNotes = [
            'primary_nationality' => 'Maldivian',
            'guest_residency' => 'local_resident',
            'invoice_total_amount' => 1200,
        ];
        $customNotes = is_array($overrides['notes'] ?? null) ? $overrides['notes'] : [];

        return (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'vendor_service_id' => null,
            'customer_name' => (string) ($overrides['customer_name'] ?? 'Flow Guest'),
            'customer_email' => (string) ($overrides['customer_email'] ?? 'flow@example.com'),
            'start_at' => $overrides['start_at'] ?? now()->addDay(),
            'end_at' => $overrides['end_at'] ?? now()->addDays(3),
            'guests' => (int) ($overrides['guests'] ?? 2),
            'total_amount' => (float) ($overrides['total_amount'] ?? 1200),
            'currency' => (string) ($overrides['currency'] ?? 'MVR'),
            'status' => (string) ($overrides['status'] ?? 'pending'),
            'payment_status' => (string) ($overrides['payment_status'] ?? 'unpaid'),
            'notes' => json_encode(array_merge($defaultNotes, $customNotes)),
            'created_at' => $overrides['created_at'] ?? now(),
            'updated_at' => $overrides['updated_at'] ?? now(),
        ]);
    }
}
