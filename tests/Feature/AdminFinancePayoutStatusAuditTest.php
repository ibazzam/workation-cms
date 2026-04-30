<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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

    private function createVendorUserId(): int
    {
        $vendor = User::factory()->create();
        return (int) $vendor->id;
    }
}
