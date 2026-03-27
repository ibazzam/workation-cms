<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorRoomCatalogValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_create_room_with_valid_catalog_values(): void
    {
        [$vendor, $propertyId] = $this->createVendorAndAccommodationProperty();

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession($this->vendorSession($vendor))
            ->post('/portal/vendor/rooms/create', [
                'vendor_property_id' => $propertyId,
                'name' => 'Deluxe King',
                'quantity' => 3,
                'max_occupancy' => 2,
                'bed_type' => 'king',
                'room_amenities' => ['wifi', 'air_conditioning'],
                'bathroom_type' => 'ensuite',
                'bathroom_count' => 1,
                'bathroom_amenities' => ['hot_water', 'toiletries'],
                'base_price' => 650,
            ]);

        $response
            ->assertStatus(302)
            ->assertSessionHas('portal_notice', 'Room category added.');

        $this->assertDatabaseHas('vendor_property_room_categories', [
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'name' => 'Deluxe King',
            'bed_type' => 'king',
            'bathroom_type' => 'ensuite',
            'bathroom_count' => 1,
        ]);

        $room = DB::table('vendor_property_room_categories')
            ->where('vendor_user_id', $vendor->id)
            ->where('vendor_property_id', $propertyId)
            ->first(['amenities', 'bathroom_amenities']);

        $this->assertNotNull($room);
        $this->assertStringContainsString('wifi', (string) ($room->amenities ?? ''));
        $this->assertStringContainsString('air_conditioning', (string) ($room->amenities ?? ''));
        $this->assertStringContainsString('hot_water', (string) ($room->bathroom_amenities ?? ''));
        $this->assertStringContainsString('toiletries', (string) ($room->bathroom_amenities ?? ''));
    }

    public function test_room_create_rejects_invalid_bed_type_outside_catalog(): void
    {
        [$vendor, $propertyId] = $this->createVendorAndAccommodationProperty();

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession($this->vendorSession($vendor))
            ->post('/portal/vendor/rooms/create', [
                'vendor_property_id' => $propertyId,
                'name' => 'Invalid Bed Room',
                'quantity' => 1,
                'max_occupancy' => 2,
                'bed_type' => 'super_emperor',
                'room_amenities' => ['wifi'],
            ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['profile']);

        $this->assertDatabaseMissing('vendor_property_room_categories', [
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'name' => 'Invalid Bed Room',
        ]);
    }

    public function test_room_update_rejects_shared_bathroom_when_count_is_zero(): void
    {
        [$vendor, $propertyId] = $this->createVendorAndAccommodationProperty();

        $roomId = (int) DB::table('vendor_property_room_categories')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'name' => 'Family Room',
            'quantity' => 2,
            'max_occupancy' => 4,
            'bed_type' => 'queen',
            'amenities' => 'wifi',
            'base_price' => 900,
            'currency' => 'MVR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession($this->vendorSession($vendor))
            ->post('/portal/vendor/rooms/' . $roomId . '/update', [
                'name' => 'Family Room',
                'quantity' => 2,
                'max_occupancy' => 4,
                'bed_type' => 'queen',
                'room_amenities' => ['wifi'],
                'bathroom_type' => 'shared',
                'bathroom_count' => 0,
                'bathroom_amenities' => ['hot_water'],
                'base_price' => 900,
            ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['profile']);

        $this->assertDatabaseMissing('vendor_property_room_categories', [
            'id' => $roomId,
            'bathroom_type' => 'shared',
            'bathroom_count' => 0,
        ]);
    }

    public function test_room_update_rejects_invalid_bathroom_amenity_outside_catalog(): void
    {
        [$vendor, $propertyId] = $this->createVendorAndAccommodationProperty();

        $roomId = (int) DB::table('vendor_property_room_categories')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'name' => 'Ocean View',
            'quantity' => 1,
            'max_occupancy' => 2,
            'bed_type' => 'queen',
            'amenities' => 'wifi',
            'bathroom_type' => 'ensuite',
            'bathroom_count' => 1,
            'bathroom_amenities' => 'hot_water',
            'base_price' => 850,
            'currency' => 'MVR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession($this->vendorSession($vendor))
            ->post('/portal/vendor/rooms/' . $roomId . '/update', [
                'name' => 'Ocean View',
                'quantity' => 1,
                'max_occupancy' => 2,
                'bed_type' => 'queen',
                'room_amenities' => ['wifi'],
                'bathroom_type' => 'ensuite',
                'bathroom_count' => 1,
                'bathroom_amenities' => ['hot_water', 'invalid_fixture'],
                'base_price' => 850,
            ]);

        $response
            ->assertStatus(302)
            ->assertSessionHasErrors(['profile']);

        $room = DB::table('vendor_property_room_categories')
            ->where('id', $roomId)
            ->first(['bathroom_amenities']);

        $this->assertNotNull($room);
        $this->assertStringContainsString('hot_water', (string) ($room->bathroom_amenities ?? ''));
        $this->assertStringNotContainsString('invalid_fixture', (string) ($room->bathroom_amenities ?? ''));
    }

    public function test_room_update_persists_bathroom_fields_with_valid_catalog_values(): void
    {
        [$vendor, $propertyId] = $this->createVendorAndAccommodationProperty();

        $roomId = (int) DB::table('vendor_property_room_categories')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'name' => 'Garden Room',
            'quantity' => 1,
            'max_occupancy' => 2,
            'bed_type' => 'double',
            'amenities' => 'wifi',
            'bathroom_type' => 'ensuite',
            'bathroom_count' => 1,
            'bathroom_amenities' => 'hot_water',
            'base_price' => 780,
            'currency' => 'MVR',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession($this->vendorSession($vendor))
            ->post('/portal/vendor/rooms/' . $roomId . '/update', [
                'name' => 'Garden Room Updated',
                'quantity' => 2,
                'max_occupancy' => 3,
                'bed_type' => 'king',
                'room_amenities' => ['wifi', 'balcony'],
                'bathroom_type' => 'private_external',
                'bathroom_count' => 2,
                'bathroom_amenities' => ['hot_water', 'toiletries'],
                'base_price' => 910,
            ]);

        $response
            ->assertStatus(302)
            ->assertSessionHas('portal_notice', 'Room category updated.');

        $this->assertDatabaseHas('vendor_property_room_categories', [
            'id' => $roomId,
            'name' => 'Garden Room Updated',
            'quantity' => 2,
            'max_occupancy' => 3,
            'bed_type' => 'king',
            'bathroom_type' => 'private_external',
            'bathroom_count' => 2,
        ]);

        $room = DB::table('vendor_property_room_categories')
            ->where('id', $roomId)
            ->first(['amenities', 'bathroom_amenities']);

        $this->assertNotNull($room);
        $this->assertStringContainsString('wifi', (string) ($room->amenities ?? ''));
        $this->assertStringContainsString('balcony', (string) ($room->amenities ?? ''));
        $this->assertStringContainsString('hot_water', (string) ($room->bathroom_amenities ?? ''));
        $this->assertStringContainsString('toiletries', (string) ($room->bathroom_amenities ?? ''));
    }

    private function createVendorAndAccommodationProperty(): array
    {
        $vendor = User::factory()->create([
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VN-ROOM-001',
        ]);

        $propertyId = (int) DB::table('vendor_properties')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'name' => 'Test Stay',
            'property_type' => 'property',
            'listing_category' => 'accommodation',
            'location' => 'Male',
            'description' => 'Accommodation listing for room validation tests',
            'status' => 'active',
            'base_price' => 0,
            'currency' => 'MVR',
            'max_guests' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$vendor, $propertyId];
    }

    private function vendorSession(User $vendor): array
    {
        return [
            'portal_vendor_authenticated' => true,
            'portal_vendor_user' => $vendor->name,
            'portal_vendor_user_id' => $vendor->id,
            'portal_vendor_role' => 'VENDOR',
        ];
    }
}
