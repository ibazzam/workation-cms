<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminVendorVerificationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_care_can_reject_vendor_with_reason_and_missing_documents(): void
    {
        $adminCare = User::factory()->create([
            'portal_role' => 'ADMIN_CARE',
            'portal_enabled' => true,
        ]);

        $vendor = User::factory()->create([
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VN-VERIFY-001',
            'vendor_verification_status' => 'under_review',
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession([
                'portal_admin_authenticated' => true,
                'portal_admin_user_id' => $adminCare->id,
                'portal_admin_role' => 'ADMIN_CARE',
            ])
            ->post('/portal/admin/users/' . $vendor->id . '/manage', [
                'portal_role' => 'VENDOR',
                'portal_enabled' => '1',
                'portal_vendor_id' => 'VN-VERIFY-001',
                'vendor_verification_status' => 'rejected',
                'vendor_verification_notes' => 'Document set is incomplete after review.',
                'vendor_rejection_reason' => 'Business license copy is expired.',
                'vendor_missing_documents' => "Updated business license\nOwner national ID",
                'crosscheck_business_profile' => '1',
                'crosscheck_service_profile' => '1',
                'crosscheck_id_proof' => '1',
                'vendor_contact_verified' => '0',
            ]);

        $response
            ->assertStatus(302)
            ->assertSessionHas('portal_notice');

        $vendor->refresh();

        $this->assertSame('rejected', strtolower((string) ($vendor->vendor_verification_status ?? '')));

        if (Schema::hasColumn('users', 'vendor_verification_rejection_reason')) {
            $this->assertSame('Business license copy is expired.', (string) $vendor->vendor_verification_rejection_reason);
        }

        if (Schema::hasColumn('users', 'vendor_verification_missing_documents')) {
            $missingDocuments = (string) ($vendor->vendor_verification_missing_documents ?? '');
            $this->assertStringContainsString('Updated business license', $missingDocuments);
            $this->assertStringContainsString('Owner national ID', $missingDocuments);
        }

        if (Schema::hasTable('portal_vendor_verification_reviews')) {
            $this->assertDatabaseHas('portal_vendor_verification_reviews', [
                'vendor_user_id' => $vendor->id,
                'reviewed_by_user_id' => $adminCare->id,
                'reviewer_role' => 'ADMIN_CARE',
                'to_status' => 'rejected',
                'crosscheck_business_profile' => 1,
                'crosscheck_service_profile' => 1,
                'crosscheck_id_proof' => 1,
                'rejection_reason' => 'Business license copy is expired.',
            ]);
        }
    }
}
