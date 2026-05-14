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
            'journey_duration_days' => 20,
            'cabin_count' => 12,
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

    public function test_liveaboard_catalog_card_prefers_room_level_price_over_property_level_base_price(): void
    {
        $listingTable = 'vendor_liveaboard_listings';
        $listingColumns = Schema::getColumnListing($listingTable);

        $listingPayload = [
            'vendor_property_id' => 7201,
            'vendor_user_id' => 91,
            'name' => 'Dolphin Cruise',
            'status' => 'active',
            'location' => 'Male',
            'description' => 'Multi-day safari journey.',
            'base_price' => 20, // must be ignored for catalog card when room rate exists
            'currency' => 'MVR',
            'listing_moderation_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $listingPayload = array_intersect_key($listingPayload, array_flip($listingColumns));
        DB::table($listingTable)->insert($listingPayload);

        if (Schema::hasTable('users')) {
            $userColumns = Schema::getColumnListing('users');
            $userPayload = [
                'id' => 91,
                'name' => 'Liveaboard Vendor',
                'username' => 'liveaboard_vendor_91',
                'email' => 'vendor91@example.test',
                'password' => bcrypt('password'),
                'portal_role' => 'VENDOR',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $userPayload = array_intersect_key($userPayload, array_flip($userColumns));
            DB::table('users')->insert($userPayload);
        }

        if (Schema::hasTable('vendor_properties')) {
            $parentColumns = Schema::getColumnListing('vendor_properties');
            $parentPayload = [
                'id' => 7201,
                'vendor_user_id' => 91,
                'name' => 'Dolphin Cruise',
                'category' => 'liveaboard',
                'status' => 'active',
                'base_price' => 20,
                'currency' => 'MVR',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $parentPayload = array_intersect_key($parentPayload, array_flip($parentColumns));
            DB::table('vendor_properties')->insert($parentPayload);
        }

        if (Schema::hasTable('vendor_property_room_categories')) {
            $roomTable = 'vendor_property_room_categories';
            $roomColumns = Schema::getColumnListing($roomTable);

            $roomPayload = [
                'vendor_property_id' => 7201,
                'property_id' => 7201,
                'vendor_user_id' => 91,
                'name' => 'Ocean Cabin',
                'currency' => 'USD',
                'base_price' => 250,
                'meal_plan_room_only_price_usd' => 250,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $roomPayload = array_intersect_key($roomPayload, array_flip($roomColumns));
            DB::table($roomTable)->insert($roomPayload);
        }

        $response = $this->get('/catalog/liveaboard', ['CF-IPCountry' => 'US']);

        $response
            ->assertOk()
            ->assertSeeText('From USD 250.00')
            ->assertDontSeeText('From USD 1.30');
    }
}
