<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminFinancePayoutStatusAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_finance_can_update_item_status_and_create_audit_log(): void
    {
        $vendorUserId = $this->createVendorUserId();

        $batchId = (int) DB::table('finance_payout_batches')->insertGetId([
            'batch_ref' => 'BATCH-MIB-MVR-20260430-001',
            'batch_date' => now()->toDateString(),
            'source_medium' => 'mib',
            'currency_band' => 'local_mvr',
            'currency' => 'MVR',
            'item_count' => 1,
            'gross_amount' => 1200,
            'commission_amount' => 120,
            'gateway_fee_amount' => 30,
            'net_payout_amount' => 1050,
            'status' => 'processing',
            'created_by_user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendorUserId,
            'vendor_property_id' => null,
            'customer_name' => 'Finance Guest',
            'customer_email' => 'finance@example.com',
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(4),
            'guests' => 2,
            'total_amount' => 1200,
            'currency' => 'MVR',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_gateway' => 'mib_mvr',
            'payment_amount' => 1200,
            'commission_amount' => 120,
            'gateway_fee_amount' => 30,
            'vendor_payout_amount' => 1050,
            'payment_collected_at' => now()->subDays(2),
            'payout_status' => 'processing',
            'notes' => json_encode([
                'category_key' => 'accommodation',
                'room_name' => 'Ocean Villa Suite',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemId = (int) DB::table('finance_payout_batch_items')->insertGetId([
            'batch_id' => $batchId,
            'vendor_user_id' => $vendorUserId,
            'reservation_ids' => json_encode([$reservationId]),
            'gross_amount' => 1200,
            'commission_amount' => 120,
            'gateway_fee_amount' => 30,
            'net_payout_amount' => 1050,
            'currency' => 'MVR',
            'status' => 'processing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vendor_reservations')->where('id', $reservationId)->update([
            'payout_batch_item_id' => $itemId,
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession([
                'portal_user' => [
                    'id' => 77,
                    'portal_role' => 'ADMIN_FINANCE',
                ],
            ])
            ->post('/portal/admin/finance/payout-items/' . $itemId . '/status', [
                'status' => 'paid',
                'bank_reference' => 'BANK-SETTLED-001',
                'notes' => 'Settled and reconciled.',
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('finance_payout_batch_items', [
            'id' => $itemId,
            'status' => 'paid',
            'bank_reference' => 'BANK-SETTLED-001',
        ]);

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'payout_status' => 'paid',
        ]);

        $this->assertDatabaseHas('finance_payout_item_status_logs', [
            'item_id' => $itemId,
            'batch_id' => $batchId,
            'from_status' => 'processing',
            'to_status' => 'paid',
            'bank_reference' => 'BANK-SETTLED-001',
            'actor_user_id' => 77,
        ]);
    }

    public function test_batch_detail_renders_reservation_accounting_rows(): void
    {
        $vendorUserId = $this->createVendorUserId();

        $batchId = (int) DB::table('finance_payout_batches')->insertGetId([
            'batch_ref' => 'BATCH-BML-MVR-20260430-002',
            'batch_date' => now()->toDateString(),
            'source_medium' => 'bml',
            'currency_band' => 'local_mvr',
            'currency' => 'MVR',
            'item_count' => 1,
            'gross_amount' => 900,
            'commission_amount' => 90,
            'gateway_fee_amount' => 20,
            'net_payout_amount' => 790,
            'status' => 'processing',
            'created_by_user_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendorUserId,
            'vendor_property_id' => null,
            'customer_name' => 'Ledger Guest',
            'customer_email' => 'ledger@example.com',
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(3),
            'guests' => 2,
            'total_amount' => 900,
            'currency' => 'MVR',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_gateway' => 'bml_mvr',
            'payment_amount' => 900,
            'commission_amount' => 90,
            'gateway_fee_amount' => 20,
            'vendor_payout_amount' => 790,
            'payment_collected_at' => now()->subDay(),
            'payout_status' => 'processing',
            'notes' => json_encode([
                'category_key' => 'accommodation',
                'room_name' => 'Sunset Suite',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $itemId = (int) DB::table('finance_payout_batch_items')->insertGetId([
            'batch_id' => $batchId,
            'vendor_user_id' => $vendorUserId,
            'reservation_ids' => json_encode([$reservationId]),
            'gross_amount' => 900,
            'commission_amount' => 90,
            'gateway_fee_amount' => 20,
            'net_payout_amount' => 790,
            'currency' => 'MVR',
            'status' => 'processing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vendor_reservations')->where('id', $reservationId)->update([
            'payout_batch_item_id' => $itemId,
            'updated_at' => now(),
        ]);

        $response = $this
            ->withSession([
                'portal_user' => [
                    'id' => 88,
                    'portal_role' => 'ADMIN_FINANCE',
                ],
            ])
            ->get('/portal/admin/finance/payouts/' . $batchId);

        $response->assertOk();
        $response->assertSee('Reservation Accounting Ledger');
        $response->assertSee('Accommodation');
        $response->assertSee('Sunset Suite');
        $response->assertSee('#' . $reservationId);
    }

    public function test_admin_finance_can_manually_approve_vendor_payout_account_with_cross_checks(): void
    {
        $vendor = User::factory()->create();

        $userUpdates = [];
        if (Schema::hasColumn('users', 'vendor_verification_status')) {
            $userUpdates['vendor_verification_status'] = 'under_review';
        }
        if (Schema::hasColumn('users', 'vendor_business_registration_number')) {
            $userUpdates['vendor_business_registration_number'] = 'BRN-7788';
        }
        if (Schema::hasColumn('users', 'vendor_business_license_number')) {
            $userUpdates['vendor_business_license_number'] = 'LIC-1122';
        }
        if (Schema::hasColumn('users', 'vendor_verification_documents')) {
            $userUpdates['vendor_verification_documents'] = json_encode([
                ['name' => 'business-license.pdf', 'path' => 'vendor/compliance-documents/1/business-license.pdf'],
            ]);
        }
        if ($userUpdates !== []) {
            DB::table('users')->where('id', (int) $vendor->id)->update($userUpdates);
        }

        DB::table('vendor_billing_details')->insert([
            'vendor_user_id' => (int) $vendor->id,
            'business_name' => 'Island Solo Ventures',
            'responsible_person_name' => 'Ahmed Shareef',
            'billing_email' => 'finance@islandsolo.test',
            'contact_number' => '+9607771122',
            'currency' => 'USD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $accountId = (int) DB::table('vendor_payout_accounts')->insertGetId([
            'vendor_user_id' => (int) $vendor->id,
            'account_label' => 'USD Sole Prop Account',
            'payout_method' => 'bank_transfer',
            'beneficiary_name' => 'Ahmed Shareef',
            'bank_account_number' => 'USD1234567890',
            'bank_account_last4' => '7890',
            'bank_name' => 'MIB',
            'swift_code' => 'MIBAADMV',
            'currency' => 'USD',
            'is_primary' => true,
            'is_active' => true,
            'verification_status' => 'pending_review',
            'verification_notes' => 'Awaiting finance review.',
            'verified_at' => null,
            'verified_by_user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession([
                'portal_user' => [
                    'id' => 901,
                    'portal_role' => 'ADMIN_FINANCE',
                ],
            ])
            ->post('/portal/admin/finance/payout-accounts/' . $accountId . '/verify', [
                'verification_status' => 'approved',
                'crosscheck_business_profile' => '1',
                'crosscheck_service_profile' => '1',
                'crosscheck_id_proof' => '1',
                'sole_proprietor_personal_name_allowed' => '1',
                'review_notes' => 'Verified documents and accepted sole proprietor personal account name.',
            ]);

        $response->assertStatus(302);

        $updatedAccount = DB::table('vendor_payout_accounts')->where('id', $accountId)->first();
        $this->assertNotNull($updatedAccount);
        $this->assertSame('approved', (string) $updatedAccount->verification_status);
        $this->assertSame(901, (int) ($updatedAccount->verified_by_user_id ?? 0));
        $this->assertNotNull($updatedAccount->verified_at);
        $this->assertStringContainsString('sole_prop_personal_name=allowed', (string) ($updatedAccount->verification_notes ?? ''));

        $this->assertDatabaseHas('finance_payout_account_verification_logs', [
            'payout_account_id' => $accountId,
            'vendor_user_id' => (int) $vendor->id,
            'from_status' => 'pending_review',
            'to_status' => 'approved',
            'crosscheck_business_profile' => true,
            'crosscheck_service_profile' => true,
            'crosscheck_id_proof' => true,
            'sole_proprietor_personal_name_allowed' => true,
            'actor_user_id' => 901,
        ]);
    }

    private function createVendorUserId(): int
    {
        $vendor = User::factory()->create();
        return (int) $vendor->id;
    }
}
