<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogCategoryPricingTest extends TestCase
{
    use RefreshDatabase;

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

        $response = $this->get('/catalog/excursion');

        $response
            ->assertOk()
            ->assertSeeText('From MVR 450.00');
    }
}
