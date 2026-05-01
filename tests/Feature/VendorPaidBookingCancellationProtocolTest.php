<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorPaidBookingCancellationProtocolTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_cannot_cancel_paid_booking_without_reason(): void
    {
        [$vendor, $reservationId] = $this->createReservationForVendor('paid', 'confirmed');

        $response = $this
            ->from('/vendor/reservations')
            ->withSession([
                'portal_vendor_authenticated' => true,
                'portal_vendor_user_id' => $vendor->id,
            ])
            ->post('/portal/vendor/reservations/' . $reservationId . '/status', [
                'status' => 'cancelled',
                'cancel_reason' => '',
            ]);

        $response
            ->assertRedirect('/vendor/reservations')
            ->assertSessionHasErrors(['profile']);

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }

    public function test_vendor_paid_booking_cancellation_is_recorded_as_cancel_request_with_reason(): void
    {
        [$vendor, $reservationId] = $this->createReservationForVendor('paid', 'confirmed');

        $response = $this
            ->from('/vendor/reservations')
            ->withSession([
                'portal_vendor_authenticated' => true,
                'portal_vendor_user_id' => $vendor->id,
            ])
            ->post('/portal/vendor/reservations/' . $reservationId . '/status', [
                'status' => 'cancelled',
                'cancel_reason' => 'Property maintenance issue requires full closure for the booked dates.',
            ]);

        $response
            ->assertRedirect('/vendor/reservations')
            ->assertSessionHas('portal_notice', 'Paid booking cancellation has been declared as a vendor cancellation request with reason and is now under review.');

        $reservation = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertNotNull($reservation);
        $this->assertSame('cancel_requested', strtolower((string) ($reservation->status ?? '')));

        $notes = json_decode((string) ($reservation->notes ?? ''), true);
        $this->assertIsArray($notes);
        $this->assertSame('vendor_portal', (string) ($notes['vendor_cancel_requested_by'] ?? ''));
        $this->assertNotSame('', trim((string) ($notes['vendor_cancel_requested_at'] ?? '')));
        $this->assertSame('confirmed', strtolower((string) ($notes['vendor_cancel_requested_from_status'] ?? '')));
        $this->assertNotSame('', trim((string) ($notes['vendor_cancel_requested_reason'] ?? '')));
    }

    private function createReservationForVendor(string $paymentStatus, string $status): array
    {
        $vendor = User::factory()->create([
            'portal_role' => 'vendor',
        ]);

        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => 0,
            'name' => 'Vendor Cancellation Protocol Test Property',
            'location' => 'Male',
            'status' => 'active',
            'max_guests' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vendor_accommodation_listings')
            ->where('id', $propertyId)
            ->update(['vendor_property_id' => $propertyId]);

        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'customer_name' => 'Protocol Customer',
            'customer_email' => 'vendor-cancel-protocol@example.com',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
            'guests' => 2,
            'total_amount' => 200,
            'currency' => 'MVR',
            'status' => $status,
            'payment_status' => $paymentStatus,
            'notes' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$vendor, $reservationId];
    }
}
