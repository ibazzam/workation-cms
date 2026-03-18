<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PortalVendorWorkflowSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_care_creates_vendor_registration_approval_request(): void
    {
        $adminCare = User::factory()->create([
            'username' => 'care_agent',
            'portal_role' => 'ADMIN_CARE',
            'portal_enabled' => true,
        ]);

        $registrationId = $this->createPendingVendorRegistration();

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession([
                'portal_admin_authenticated' => true,
                'portal_admin_user' => $adminCare->name,
                'portal_admin_user_id' => $adminCare->id,
                'portal_admin_role' => 'ADMIN_CARE',
            ])
            ->post('/portal/admin/vendor-registrations/' . $registrationId . '/approve', [
                'portal_vendor_id' => 'VENDOR-001',
                'approval_notes' => 'Looks valid, requesting approval.',
            ]);

        $response
            ->assertStatus(302)
            ->assertSessionHas('portal_notice', 'Vendor approval request submitted for ADMIN/ADMIN_SUPER approval.');

        $this->assertDatabaseHas('portal_admin_action_requests', [
            'action_type' => 'vendor_registration_approve',
            'target_registration_id' => $registrationId,
            'status' => 'pending',
            'requested_by_user_id' => $adminCare->id,
        ]);

        $this->assertDatabaseHas('vendor_registration_requests', [
            'id' => $registrationId,
            'status' => 'pending',
        ]);
    }

    public function test_admin_finance_cannot_review_vendor_registration_requests(): void
    {
        $registrationId = $this->createPendingVendorRegistration();

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession([
                'portal_admin_authenticated' => true,
                'portal_admin_role' => 'ADMIN_FINANCE',
            ])
            ->post('/portal/admin/vendor-registrations/' . $registrationId . '/reject', [
                'review_notes' => 'Not allowed.',
            ]);

        $response->assertForbidden();
    }

    public function test_admin_finance_cannot_approve_vendor_registration_action_request(): void
    {
        $adminFinance = User::factory()->create([
            'username' => 'finance_admin',
            'portal_role' => 'ADMIN_FINANCE',
            'portal_enabled' => true,
        ]);

        $registrationId = $this->createPendingVendorRegistration('vendor.finance.blocked@example.com');

        $requestId = (int) DB::table('portal_admin_action_requests')->insertGetId([
            'action_type' => 'vendor_registration_approve',
            'requested_by_role' => 'ADMIN_CARE',
            'target_registration_id' => $registrationId,
            'target_identifier' => 'vendor.finance.blocked@example.com',
            'payload' => json_encode([
                'portal_vendor_id' => 'VN-REG-500',
                'approval_notes' => 'Should not be approved by finance.',
            ]),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession([
                'portal_admin_authenticated' => true,
                'portal_admin_user' => $adminFinance->name,
                'portal_admin_user_id' => $adminFinance->id,
                'portal_admin_role' => 'ADMIN_FINANCE',
            ])
            ->post('/portal/admin/action-requests/' . $requestId . '/approve');

        $response->assertForbidden();

        $this->assertDatabaseHas('portal_admin_action_requests', [
            'id' => $requestId,
            'status' => 'pending',
            'approved_by_user_id' => null,
        ]);

        $this->assertDatabaseHas('vendor_registration_requests', [
            'id' => $registrationId,
            'status' => 'pending',
        ]);

        $this->assertDatabaseMissing('users', [
            'email' => 'vendor.finance.blocked@example.com',
        ]);
    }

    public function test_admin_finance_cannot_reject_vendor_registration_action_request(): void
    {
        $adminFinance = User::factory()->create([
            'username' => 'finance_reject_admin',
            'portal_role' => 'ADMIN_FINANCE',
            'portal_enabled' => true,
        ]);

        $registrationId = $this->createPendingVendorRegistration('vendor.finance.reject.blocked@example.com');

        $requestId = (int) DB::table('portal_admin_action_requests')->insertGetId([
            'action_type' => 'vendor_registration_approve',
            'requested_by_role' => 'ADMIN_CARE',
            'target_registration_id' => $registrationId,
            'target_identifier' => 'vendor.finance.reject.blocked@example.com',
            'payload' => json_encode([
                'portal_vendor_id' => 'VN-REG-501',
                'approval_notes' => 'Should not be rejected by finance.',
            ]),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession([
                'portal_admin_authenticated' => true,
                'portal_admin_user' => $adminFinance->name,
                'portal_admin_user_id' => $adminFinance->id,
                'portal_admin_role' => 'ADMIN_FINANCE',
            ])
            ->post('/portal/admin/action-requests/' . $requestId . '/reject', [
                'reason' => 'Finance cannot reject vendor registration requests.',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('portal_admin_action_requests', [
            'id' => $requestId,
            'status' => 'pending',
            'approved_by_user_id' => null,
            'rejection_reason' => null,
        ]);

        $this->assertDatabaseHas('vendor_registration_requests', [
            'id' => $registrationId,
            'status' => 'pending',
        ]);
    }

    public function test_admin_delete_vendor_creates_pending_action_request(): void
    {
        $admin = User::factory()->create([
            'username' => 'ops_admin',
            'portal_role' => 'ADMIN',
            'portal_enabled' => true,
        ]);

        $vendor = User::factory()->create([
            'username' => 'vendor_user',
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VN-100',
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession([
                'portal_admin_authenticated' => true,
                'portal_admin_user' => $admin->name,
                'portal_admin_user_id' => $admin->id,
                'portal_admin_role' => 'ADMIN',
            ])
            ->delete('/portal/admin/users/' . $vendor->id . '/delete');

        $response
            ->assertStatus(302)
            ->assertSessionHas('portal_notice', 'Vendor delete request submitted for ADMIN_SUPER approval.');

        $this->assertDatabaseHas('users', [
            'id' => $vendor->id,
            'portal_role' => 'VENDOR',
        ]);

        $this->assertDatabaseHas('portal_admin_action_requests', [
            'action_type' => 'vendor_delete',
            'target_user_id' => $vendor->id,
            'status' => 'pending',
            'requested_by_user_id' => $admin->id,
        ]);
    }

    public function test_admin_super_can_approve_vendor_delete_action_request(): void
    {
        $adminSuper = User::factory()->create([
            'username' => 'super_admin',
            'portal_role' => 'ADMIN_SUPER',
            'portal_enabled' => true,
        ]);

        $requester = User::factory()->create([
            'username' => 'requester_admin',
            'portal_role' => 'ADMIN',
            'portal_enabled' => true,
        ]);

        $vendor = User::factory()->create([
            'username' => 'vendor_delete_me',
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VN-200',
        ]);

        $requestId = (int) DB::table('portal_admin_action_requests')->insertGetId([
            'action_type' => 'vendor_delete',
            'requested_by_user_id' => $requester->id,
            'requested_by_role' => 'ADMIN',
            'target_user_id' => $vendor->id,
            'target_identifier' => $vendor->username,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession([
                'portal_admin_authenticated' => true,
                'portal_admin_user' => $adminSuper->name,
                'portal_admin_user_id' => $adminSuper->id,
                'portal_admin_role' => 'ADMIN_SUPER',
            ])
            ->post('/portal/admin/action-requests/' . $requestId . '/approve');

        $response
            ->assertStatus(302)
            ->assertSessionHas('portal_notice', 'Vendor delete request approved and processed.');

        $this->assertDatabaseMissing('users', [
            'id' => $vendor->id,
        ]);

        $this->assertDatabaseHas('portal_admin_action_requests', [
            'id' => $requestId,
            'status' => 'approved',
            'approved_by_user_id' => $adminSuper->id,
        ]);
    }

    public function test_admin_can_approve_vendor_registration_action_request(): void
    {
        $admin = User::factory()->create([
            'username' => 'approver_admin',
            'portal_role' => 'ADMIN',
            'portal_enabled' => true,
        ]);

        $registrationId = $this->createPendingVendorRegistration('vendor.admin.approval@example.com');

        $requestId = (int) DB::table('portal_admin_action_requests')->insertGetId([
            'action_type' => 'vendor_registration_approve',
            'requested_by_user_id' => $admin->id,
            'requested_by_role' => 'ADMIN_CARE',
            'target_registration_id' => $registrationId,
            'target_identifier' => 'vendor.admin.approval@example.com',
            'payload' => json_encode([
                'portal_vendor_id' => 'VN-REG-300',
                'approval_notes' => 'Approved by admin.',
            ]),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession([
                'portal_admin_authenticated' => true,
                'portal_admin_user' => $admin->name,
                'portal_admin_user_id' => $admin->id,
                'portal_admin_role' => 'ADMIN',
            ])
            ->post('/portal/admin/action-requests/' . $requestId . '/approve');

        $response
            ->assertStatus(302)
            ->assertSessionHas('portal_notice', 'Vendor registration approval request processed successfully.');

        $this->assertDatabaseHas('vendor_registration_requests', [
            'id' => $registrationId,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('portal_admin_action_requests', [
            'id' => $requestId,
            'status' => 'approved',
            'approved_by_user_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'vendor.admin.approval@example.com',
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VN-REG-300',
        ]);
    }

    public function test_admin_super_can_approve_vendor_registration_action_request(): void
    {
        $adminSuper = User::factory()->create([
            'username' => 'super_reg_approver',
            'portal_role' => 'ADMIN_SUPER',
            'portal_enabled' => true,
        ]);

        $requester = User::factory()->create([
            'username' => 'care_requester',
            'portal_role' => 'ADMIN_CARE',
            'portal_enabled' => true,
        ]);

        $registrationId = $this->createPendingVendorRegistration('vendor.super.approval@example.com');

        $requestId = (int) DB::table('portal_admin_action_requests')->insertGetId([
            'action_type' => 'vendor_registration_approve',
            'requested_by_user_id' => $requester->id,
            'requested_by_role' => 'ADMIN_CARE',
            'target_registration_id' => $registrationId,
            'target_identifier' => 'vendor.super.approval@example.com',
            'payload' => json_encode([
                'portal_vendor_id' => 'VN-REG-400',
                'approval_notes' => 'Approved by super admin.',
            ]),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession([
                'portal_admin_authenticated' => true,
                'portal_admin_user' => $adminSuper->name,
                'portal_admin_user_id' => $adminSuper->id,
                'portal_admin_role' => 'ADMIN_SUPER',
            ])
            ->post('/portal/admin/action-requests/' . $requestId . '/approve');

        $response
            ->assertStatus(302)
            ->assertSessionHas('portal_notice', 'Vendor registration approval request processed successfully.');

        $this->assertDatabaseHas('vendor_registration_requests', [
            'id' => $registrationId,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('portal_admin_action_requests', [
            'id' => $requestId,
            'status' => 'approved',
            'approved_by_user_id' => $adminSuper->id,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'vendor.super.approval@example.com',
            'portal_role' => 'VENDOR',
            'portal_enabled' => true,
            'portal_vendor_id' => 'VN-REG-400',
        ]);
    }

    private function createPendingVendorRegistration(string $email = 'vendor.pending@example.com'): int
    {
        return (int) DB::table('vendor_registration_requests')->insertGetId([
            'business_name' => 'Demo Vendor LLC',
            'contact_name' => 'Demo Contact',
            'email' => $email,
            'phone' => '555-0100',
            'business_registration_number' => 'BRN-100',
            'license_number' => 'LIC-100',
            'business_license_document_path' => 'vendor-registration-documents/demo-license.pdf',
            'verification_document_path' => 'vendor-registration-documents/demo-verification.pdf',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
