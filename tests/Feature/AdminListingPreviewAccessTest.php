<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminListingPreviewAccessTest extends TestCase
{
    use RefreshDatabase;

    private function insertPendingAccommodationListing(int $vendorPropertyId): void
    {
        $table = 'vendor_accommodation_listings';
        $columns = Schema::getColumnListing($table);

        $payload = [
            'vendor_property_id' => $vendorPropertyId,
            'vendor_user_id' => 1,
            'name' => 'Pending Preview Listing',
            'status' => 'pending_review',
            'listing_moderation_status' => 'pending_review',
            'location' => 'Maldives',
            'description' => 'Pending listing for moderation preview test.',
            'details' => json_encode([], JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $payload = array_intersect_key($payload, array_flip($columns));
        DB::table($table)->insert($payload);
    }

    public function test_listing_preview_route_is_forbidden_without_admin_moderation_role(): void
    {
        $this->insertPendingAccommodationListing(9201);

        $this->get('/portal/admin/listings/9201/preview')
            ->assertStatus(403);
    }

    public function test_admin_care_can_open_pending_listing_preview_page(): void
    {
        $adminCare = User::factory()->create([
            'portal_role' => 'ADMIN_CARE',
            'portal_enabled' => true,
        ]);

        $this->insertPendingAccommodationListing(9202);

        $this->withSession([
            'portal_admin_authenticated' => true,
            'portal_admin_user_id' => $adminCare->id,
            'portal_admin_role' => 'ADMIN_CARE',
        ])
            ->get('/portal/admin/listings/9202/preview')
            ->assertStatus(302)
            ->assertRedirect('/property/9202?preview=admin');

        $this->withSession([
            'portal_admin_authenticated' => true,
            'portal_admin_user_id' => $adminCare->id,
            'portal_admin_role' => 'ADMIN_CARE',
        ])
            ->get('/property/9202?preview=admin')
            ->assertOk()
            ->assertSeeText('Admin moderation preview mode');
    }
}
