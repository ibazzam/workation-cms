<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorReservationDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_remove_cancelled_unpaid_booking_from_portal_list(): void
    {
        $vendor = User::factory()->create();
        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => 0,
            'name' => 'Vendor Delete Test Property',
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
            'customer_name' => 'Portal Guest',
            'customer_email' => 'guest@example.com',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
            'guests' => 2,
            'total_amount' => 100,
            'invoice_total_amount' => 100,
            'currency' => 'MVR',
            'payment_currency' => 'MVR',
            'status' => 'cancelled',
            'payment_status' => 'unpaid',
            'notes' => json_encode(['category_key' => 'accommodation']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->from('/vendor?page=reservations&scope=history&category=accommodation')
            ->withSession([
                'portal_vendor_authenticated' => true,
                'portal_vendor_user_id' => $vendor->id,
                'portal_vendor_user' => $vendor->name,
            ])
            ->post('/portal/vendor/reservations/' . $reservationId . '/delete');

        $response
            ->assertRedirect('/vendor?page=reservations&scope=history&category=accommodation')
            ->assertSessionHas('portal_notice', 'Booking removed from your vendor portal list.');

        $reservation = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertNotNull($reservation);

        $notes = json_decode((string) ($reservation->notes ?? ''), true);
        $this->assertIsArray($notes);
        $this->assertNotSame('', trim((string) ($notes['vendor_deleted_at'] ?? '')));
        $this->assertSame('vendor_portal', (string) ($notes['vendor_deleted_by'] ?? ''));

        $listingResponse = $this
            ->withSession([
                'portal_vendor_authenticated' => true,
                'portal_vendor_user_id' => $vendor->id,
                'portal_vendor_user' => $vendor->name,
            ])
            ->get('/vendor?page=reservations&scope=history&category=accommodation');

        $listingResponse->assertOk();
        $listingResponse->assertDontSee('RSV-' . str_pad((string) $reservationId, 6, '0', STR_PAD_LEFT));
    }

    public function test_vendor_cannot_remove_cancelled_paid_booking_with_financial_history(): void
    {
        $vendor = User::factory()->create();
        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => 0,
            'name' => 'Vendor Delete Paid Test Property',
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
            'customer_name' => 'Portal Guest',
            'customer_email' => 'guest@example.com',
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(2),
            'guests' => 2,
            'total_amount' => 140,
            'invoice_total_amount' => 140,
            'currency' => 'MVR',
            'payment_currency' => 'MVR',
            'status' => 'cancelled',
            'payment_status' => 'paid',
            'payout_status' => 'cancelled',
            'notes' => json_encode(['category_key' => 'accommodation']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->from('/vendor?page=reservations&scope=history&category=accommodation')
            ->withSession([
                'portal_vendor_authenticated' => true,
                'portal_vendor_user_id' => $vendor->id,
                'portal_vendor_user' => $vendor->name,
            ])
            ->post('/portal/vendor/reservations/' . $reservationId . '/delete');

        $response
            ->assertRedirect('/vendor?page=reservations&scope=history&category=accommodation')
            ->assertSessionHasErrors(['profile']);

        $reservation = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertNotNull($reservation);

        $notes = json_decode((string) ($reservation->notes ?? ''), true);
        $notes = is_array($notes) ? $notes : [];
        $this->assertSame('', trim((string) ($notes['vendor_deleted_at'] ?? '')));
    }
}