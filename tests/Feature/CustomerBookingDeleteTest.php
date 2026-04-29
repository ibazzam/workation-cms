<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerBookingDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_delete_unpaid_booking_from_portal(): void
    {
        $vendor = User::factory()->create();
        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => 0,
            'name' => 'Customer Delete Test Property',
            'location' => 'Male',
            'status' => 'active',
            'max_guests' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vendor_accommodation_listings')->where('id', $propertyId)->update(['vendor_property_id' => $propertyId]);

        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'customer_name' => 'Portal Customer',
            'customer_email' => 'customer@example.com',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
            'guests' => 2,
            'total_amount' => 100,
            'currency' => 'MVR',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'notes' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withSession([
                'portal_customer_authenticated' => true,
                'portal_customer_email' => 'customer@example.com',
            ])
            ->post('/customer/bookings/' . $reservationId . '/delete');

        $response
            ->assertRedirect('/customer')
            ->assertSessionHas('portal_notice', 'Booking removed from your portal list.');

        $reservation = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertNotNull($reservation);
        $this->assertSame('cancelled', strtolower((string) ($reservation->status ?? '')));

        $notes = json_decode((string) ($reservation->notes ?? ''), true);
        $this->assertIsArray($notes);
        $this->assertNotSame('', trim((string) ($notes['customer_deleted_at'] ?? '')));
        $this->assertSame('customer_portal', (string) ($notes['customer_deleted_by'] ?? ''));
    }

    public function test_customer_cannot_delete_paid_booking_from_portal(): void
    {
        $vendor = User::factory()->create();
        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => 0,
            'name' => 'Customer Delete Test Property',
            'location' => 'Male',
            'status' => 'active',
            'max_guests' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vendor_accommodation_listings')->where('id', $propertyId)->update(['vendor_property_id' => $propertyId]);

        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'customer_name' => 'Portal Customer',
            'customer_email' => 'customer@example.com',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
            'guests' => 2,
            'total_amount' => 140,
            'currency' => 'MVR',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'notes' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->from('/customer')
            ->withSession([
                'portal_customer_authenticated' => true,
                'portal_customer_email' => 'customer@example.com',
            ])
            ->post('/customer/bookings/' . $reservationId . '/delete');

        $response
            ->assertRedirect('/customer')
            ->assertSessionHasErrors(['customer']);

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }

    public function test_customer_can_request_cancellation_for_paid_booking(): void
    {
        $vendor = User::factory()->create();
        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => 0,
            'name' => 'Customer Cancel Request Property',
            'location' => 'Male',
            'status' => 'active',
            'max_guests' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vendor_accommodation_listings')->where('id', $propertyId)->update(['vendor_property_id' => $propertyId]);

        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'customer_name' => 'Portal Customer',
            'customer_email' => 'customer@example.com',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
            'guests' => 2,
            'total_amount' => 140,
            'currency' => 'MVR',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'notes' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withSession([
                'portal_customer_authenticated' => true,
                'portal_customer_email' => 'customer@example.com',
            ])
            ->post('/customer/bookings/' . $reservationId . '/cancel');

        $response
            ->assertRedirect('/customer')
            ->assertSessionHas('portal_notice', static function ($notice): bool {
                $noticeText = (string) $notice;
                return str_starts_with($noticeText, 'Cancellation request submitted.')
                    && (str_contains($noticeText, 'under review') || str_contains($noticeText, 'confirm refund and cancellation details'));
            });

        $reservation = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertNotNull($reservation);
        $this->assertSame('cancel_requested', strtolower((string) ($reservation->status ?? '')));

        $notes = json_decode((string) ($reservation->notes ?? ''), true);
        $this->assertIsArray($notes);
        $this->assertNotSame('', trim((string) ($notes['customer_cancel_requested_at'] ?? '')));
        $this->assertSame('customer_portal', (string) ($notes['customer_cancel_requested_by'] ?? ''));
    }
}