<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PropertyNearbyPricingTest extends TestCase
{
    use RefreshDatabase;

    private function insertAccommodationListing(array $attributes): void
    {
        $table = 'vendor_accommodation_listings';
        $columns = Schema::getColumnListing($table);
        $payload = [
            'vendor_property_id' => 0,
            'vendor_user_id' => 1,
            'name' => 'Accommodation Listing',
            'status' => 'active',
            'listing_moderation_status' => 'approved',
            'location' => 'Maldives',
            'description' => 'Accommodation test listing',
            'details' => json_encode([], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach (['island' => 'Maafushi', 'city' => 'Maafushi', 'atoll' => 'Kaafu'] as $key => $value) {
            if (in_array($key, $columns, true)) {
                $payload[$key] = $value;
            }
        }

        $payload = array_merge($payload, $attributes);
        $payload = array_intersect_key($payload, array_flip($columns));

        DB::table($table)->insert($payload);
    }

    public function test_property_nearby_cards_show_price_from_room_rates(): void
    {
        $currentPropertyId = 7101;
        $nearbyPropertyId = 7102;

        $this->insertAccommodationListing([
            'vendor_property_id' => $currentPropertyId,
            'vendor_user_id' => 101,
            'name' => 'Current Stay',
            'status' => 'active',
            'listing_moderation_status' => 'approved',
        ]);

        $this->insertAccommodationListing([
            'vendor_property_id' => $nearbyPropertyId,
            'vendor_user_id' => 101,
            'name' => 'Nearby Villa',
            'status' => 'active',
            'listing_moderation_status' => 'approved',
        ]);

        DB::table('accommodation_rooms')->insert([
            [
                'property_id' => $currentPropertyId,
                'room_type' => 'double',
                'capacity_guests' => 2,
                'total_rooms_available' => 2,
                'base_price_per_night' => 650,
                'currency' => 'MVR',
                'description' => 'Current property room',
                'max_occupancy' => 2,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'property_id' => $nearbyPropertyId,
                'room_type' => 'suite',
                'capacity_guests' => 2,
                'total_rooms_available' => 3,
                'base_price_per_night' => 920,
                'currency' => 'MVR',
                'description' => 'Nearby property room',
                'max_occupancy' => 2,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->get('/property/' . $currentPropertyId);

        $response
            ->assertOk()
            ->assertSeeText('Nearby Properties')
            ->assertSeeText('Nearby Villa')
            ->assertSeeText('From MVR 920.00');
    }
}
