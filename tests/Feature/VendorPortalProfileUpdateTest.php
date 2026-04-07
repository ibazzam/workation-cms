<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class VendorPortalProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_vendor_can_update_profile_settings(): void
    {
        $vendor = User::factory()->create([
            'name' => 'Old Vendor Name',
            'email' => 'vendor.profile@example.com',
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VENDOR-900',
        ]);

        $this->withSession([
            'portal_vendor_authenticated' => true,
            'portal_vendor_user' => $vendor->name,
            'portal_vendor_user_id' => $vendor->id,
            'portal_vendor_role' => 'VENDOR',
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/portal/vendor/profile/update', [
                'display_name' => 'New Vendor Name',
                'contact_phone' => '+9607770123',
                'company_name' => 'Workation Vendor Co',
                'business_registration_number' => 'REG-2026-0001',
                'business_license_number' => 'LIC-7788',
                'contact_person_name' => 'Vendor Contact',
                'contact_person_phone' => '+9607770456',
                'contact_person_email' => 'contact@vendor.example.com',
                'contact_person_id_number' => 'A123456',
            ]);

        $response
            ->assertStatus(302)
            ->assertSessionHas('portal_notice', 'Profile and compliance details saved. Verification review status updated.')
            ->assertSessionHas('portal_vendor_user', 'New Vendor Name');

        $this->assertDatabaseHas('users', [
            'id' => $vendor->id,
            'name' => 'New Vendor Name',
        ]);
    }
}
