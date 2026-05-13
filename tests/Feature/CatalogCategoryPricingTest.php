<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogCategoryPricingTest extends TestCase
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

    public function test_non_accommodation_catalog_card_uses_derived_price_from_details_json(): void
    {
        DB::table('vendor_excursion_listings')->insert([
            'vendor_property_id' => 5101,
            'vendor_user_id' => 77,
            'name' => 'Sunset Lagoon Cruise',
            'status' => 'active',
            'location' => 'Maafushi',
            'description' => 'Evening excursion cruise.',
            'base_price' => 0,
            'currency' => 'MVR',
            'max_guests' => 12,
            'details' => json_encode([
                'pricing' => [
                    'adult_rate' => 450,
                ],
            ], JSON_THROW_ON_ERROR),
            'listing_moderation_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/catalog/excursion', ['CF-IPCountry' => 'MV']);

        $response
            ->assertOk()
            ->assertSeeText('From MVR 450.00');
    }

    public function test_accommodation_catalog_card_uses_room_price_when_listing_base_price_is_missing(): void
    {
        $propertyId = 6101;

        $this->insertAccommodationListing([
            'vendor_property_id' => $propertyId,
            'vendor_user_id' => 88,
            'name' => 'Lagoon Breeze Hotel',
            'status' => 'active',
            'listing_moderation_status' => 'approved',
        ]);

        DB::table('accommodation_rooms')->insert([
            'property_id' => $propertyId,
            'room_type' => 'double',
            'capacity_guests' => 2,
            'total_rooms_available' => 4,
            'base_price_per_night' => 780,
            'currency' => 'MVR',
            'description' => 'Test room',
            'max_occupancy' => 2,
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/catalog/accommodation', ['CF-IPCountry' => 'MV']);

        $response
            ->assertOk()
            ->assertSeeText('From MVR 780.00');
    }

    public function test_liveaboard_catalog_card_uses_lowest_price_from_string_encoded_pricing_matrix(): void
    {
        $table = 'vendor_liveaboard_listings';
        $columns = Schema::getColumnListing($table);
        $payload = [
            'vendor_property_id' => 7101,
            'vendor_user_id' => 91,
            'name' => 'Atoll Explorer Liveaboard',
            'status' => 'active',
            'location' => 'Male',
            'description' => 'Multi-day safari journey.',
            'base_price' => 0,
            'currency' => 'MVR',
            'listing_moderation_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $detailsPayload = json_encode([
            'pricing_matrix' => '{"Male→Ari":4200,"Male→Baa":5000}',
        ], JSON_THROW_ON_ERROR);

        if (in_array('listing_details', $columns, true)) {
            $payload['listing_details'] = $detailsPayload;
        }
        if (in_array('details', $columns, true)) {
            $payload['details'] = $detailsPayload;
        }

        $payload = array_intersect_key($payload, array_flip($columns));

        DB::table($table)->insert($payload);

        $response = $this->get('/catalog/liveaboard', ['CF-IPCountry' => 'MV']);

        $response
            ->assertOk()
            ->assertSeeText('From MVR 4,200.00');
    }
}
