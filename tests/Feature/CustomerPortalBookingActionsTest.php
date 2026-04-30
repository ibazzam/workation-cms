<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerPortalBookingActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_portal_shows_pay_now_for_unpaid_booking(): void
    {
        $reservationId = $this->createReservation('customer-actions@example.com', 'pending', 'unpaid');

        $response = $this->withSession([
            'portal_customer_authenticated' => true,
            'portal_customer_email' => 'customer-actions@example.com',
        ])->get('/customer');

        $response->assertOk();
        $response->assertSee('/booking/checkout/' . $reservationId, false);
        $response->assertSee('Pay Now');
    }

    public function test_customer_portal_shows_request_refund_for_paid_booking(): void
    {
        $reservationId = $this->createReservation('customer-actions@example.com', 'confirmed', 'paid');

        $response = $this->withSession([
            'portal_customer_authenticated' => true,
            'portal_customer_email' => 'customer-actions@example.com',
        ])->get('/customer');

        $response->assertOk();
        $response->assertSee('/customer/bookings/' . $reservationId . '/cancel', false);
        $response->assertSee('Request Refund');
        $response->assertDontSee('Pay Now');
    }

    public function test_customer_portal_shows_refund_timeline_and_rejection_reason(): void
    {
        $reservationId = $this->createReservation('customer-actions@example.com', 'cancel_requested', 'paid');
        $requestedAt = Carbon::create(2026, 4, 26, 9, 0, 0);
        $reviewStartedAt = Carbon::create(2026, 4, 27, 10, 15, 0);
        $rejectedAt = Carbon::create(2026, 4, 28, 14, 45, 0);

        DB::table('finance_refund_cases')->insert([
            'case_ref' => 'RFND-20260428-REJECT',
            'reservation_id' => $reservationId,
            'vendor_user_id' => 1,
            'customer_user_id' => 1,
            'original_gateway' => 'bml',
            'original_payment_reference' => 'pay-ref-1',
            'original_amount' => 140,
            'original_currency' => 'MVR',
            'source_medium' => 'bank_card',
            'currency_band' => 'domestic',
            'refund_amount' => 140,
            'refund_currency' => 'MVR',
            'refund_type' => 'full',
            'reason_notes' => 'Customer asked to cancel after payment.',
            'requested_by_role' => 'CUSTOMER',
            'requested_by_user_id' => 1,
            'status' => 'rejected',
            'reviewed_by_user_id' => 99,
            'reviewed_at' => $rejectedAt,
            'review_started_at' => $reviewStartedAt,
            'rejected_at' => $rejectedAt,
            'resolution_notes' => 'Non-refundable booking period',
            'created_at' => $requestedAt,
            'updated_at' => $rejectedAt,
        ]);

        $response = $this->withSession([
            'portal_customer_authenticated' => true,
            'portal_customer_email' => 'customer-actions@example.com',
        ])->get('/customer');

        $response->assertOk();
        $response->assertSee('Cancellation / Refund Request Timeline');
        $response->assertSee('Requested');
        $response->assertSee('Under Review');
        $response->assertSee('Rejected');
        $response->assertSee('Why it was rejected:');
        $response->assertSee('Non-refundable booking period');
        $response->assertDontSee('Refund Completed');
    }

    private function createReservation(string $customerEmail, string $status, string $paymentStatus): int
    {
        $vendor = User::factory()->create();

        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => 0,
            'name' => 'Customer Portal Action Property',
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
            'customer_name' => 'Portal Customer',
            'customer_email' => $customerEmail,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
            'guests' => 2,
            'total_amount' => 140,
            'currency' => 'MVR',
            'status' => $status,
            'payment_status' => $paymentStatus,
            'notes' => json_encode([
                'category_key' => 'accommodation',
                'room_name' => 'Hoara duplex',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
