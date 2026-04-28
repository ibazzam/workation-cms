<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorPortalNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_category_navigation_preserves_category_scope_for_redirect_routes(): void
    {
        $vendor = User::factory()->create();
        $vendor->forceFill([
            'vendor_verification_status' => 'approved',
            'vendor_approved_service_categories' => json_encode(['accommodation']),
        ])->save();

        $reservationsResponse = $this
            ->withSession([
                'portal_vendor_authenticated' => true,
                'portal_vendor_user_id' => $vendor->id,
                'portal_vendor_user' => $vendor->name,
            ])
            ->get('/vendor/reservations?category=accommodation');

        $reservationsResponse->assertRedirect('/vendor?page=reservations&category=accommodation');

        $pricingResponse = $this
            ->withSession([
                'portal_vendor_authenticated' => true,
                'portal_vendor_user_id' => $vendor->id,
                'portal_vendor_user' => $vendor->name,
            ])
            ->get('/vendor/pricing?category=accommodation');

        $pricingResponse->assertRedirect('/vendor?page=pricing&category=accommodation');
    }

    public function test_vendor_listings_console_links_keep_forced_category_scope(): void
    {
        $vendor = User::factory()->create();
        $vendor->forceFill([
            'vendor_verification_status' => 'approved',
            'vendor_approved_service_categories' => json_encode(['accommodation']),
        ])->save();

        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => 0,
            'name' => 'Scope Test Villa',
            'location' => 'Male',
            'status' => 'active',
            'listing_moderation_status' => 'approved',
            'max_guests' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vendor_accommodation_listings')
            ->where('id', $propertyId)
            ->update(['vendor_property_id' => $propertyId]);

        DB::table('vendor_reservations')->insert([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'customer_name' => 'Portal Guest',
            'customer_email' => 'portal@example.com',
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(4),
            'guests' => 2,
            'total_amount' => 900,
            'currency' => 'MVR',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'notes' => json_encode(['invoice_total_amount' => 900]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withSession([
                'portal_vendor_authenticated' => true,
                'portal_vendor_user_id' => $vendor->id,
                'portal_vendor_user' => $vendor->name,
                'portal_listing_category' => 'accommodation',
            ])
            ->get('/vendor?page=listings&category=accommodation');

        $response->assertOk();
        $response->assertSee('/vendor/reservations?category=accommodation#vendorAvailabilitySection', false);
        $response->assertSee('/vendor/pricing?category=accommodation#vendorPricingSection', false);
        $response->assertSee('Accommodation Listings');
    }
}