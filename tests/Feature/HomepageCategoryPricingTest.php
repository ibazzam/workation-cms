<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomepageCategoryPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_experiences_card_uses_bucketed_water_sports_price(): void
    {
        DB::table('vendor_water_sports_listings')->insert([
            'vendor_property_id' => 4101,
            'vendor_user_id' => 77,
            'name' => 'Lagoon Snorkeling Adventure',
            'status' => 'active',
            'location' => 'Maafushi',
            'description' => 'Half-day water sports package.',
            'base_price' => 775.00,
            'currency' => 'MVR',
            'max_guests' => 8,
            'details' => json_encode(['transport_mode' => 'speedboat'], JSON_THROW_ON_ERROR),
            'listing_moderation_status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertViewHas('homeBrowseCards', function ($cards): bool {
                $experiencesCard = collect($cards)->firstWhere('title', 'Experiences');

                return is_array($experiencesCard)
                    && ($experiencesCard['price_label'] ?? null) === 'MVR 775.00';
            });
    }
}
