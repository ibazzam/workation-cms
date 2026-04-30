<?php

namespace Tests\Feature;

use App\Finance\LedgerWriter;
use App\Finance\PayoutBatchBuilder;
use App\Finance\RefundRouter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingToPayoutAndRefundLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_cycle_bml_payment_to_payout_and_post_payout_refund_receivable(): void
    {
        $reservationId = $this->createReservationForCustomer('customer.lifecycle@example.com', Carbon::now()->subDays(16));

        $this->postJson('/booking/payment/webhooks/bml_mvr', [
            'localId' => 'WRK-' . $reservationId . '-ABCD1234',
            'transactionId' => 'BMLTXN-POST-PAYOUT-001',
            'state' => 'CONFIRMED',
            'amount' => 20000,
            'currency' => 'MVR',
        ])
            ->assertOk()
            ->assertJson(['ok' => true, 'result' => 'paid']);

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_reference' => 'BMLTXN-POST-PAYOUT-001',
        ]);

        $builder = app(PayoutBatchBuilder::class);
        $created = $builder->buildBatchesForDate(Carbon::today(), 1);
        $this->assertNotEmpty($created);

        $batch = DB::table('finance_payout_batches')->orderByDesc('id')->first();
        $this->assertNotNull($batch);

        $builder->markBatchSent((int) $batch->id, 'BANK-REF-POST-001', 1, Carbon::today()->addDay()->endOfDay());
        $builder->confirmBatchSettled((int) $batch->id, 1);

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'payout_status' => 'paid',
        ]);

        $this->withSession([
            'portal_customer_authenticated' => true,
            'portal_customer_email' => 'customer.lifecycle@example.com',
        ])
            ->post('/customer/bookings/' . $reservationId . '/cancel')
            ->assertRedirect('/customer');

        $refundCase = DB::table('finance_refund_cases')
            ->where('reservation_id', $reservationId)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($refundCase);

        $router = new RefundRouter(new LedgerWriter());
        $router->approveCase((string) $refundCase->case_ref, 1);
        $router->completeCase((string) $refundCase->case_ref, 'REFUND-POST-001', 1);

        $this->assertDatabaseHas('finance_refund_cases', [
            'id' => (int) $refundCase->id,
            'status' => 'completed',
            'offset_mode' => 'post_payout_receivable',
        ]);
        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'status' => 'cancel_requested',
            'payout_status' => 'on_hold',
        ]);
    }

    public function test_full_cycle_bml_payment_to_queued_payout_then_pre_payout_refund_deduction(): void
    {
        $reservationId = $this->createReservationForCustomer('customer.prededuct@example.com', Carbon::now()->subDays(16));

        $this->postJson('/booking/payment/webhooks/bml_mvr', [
            'localId' => 'WRK-' . $reservationId . '-ZXCV9876',
            'transactionId' => 'BMLTXN-PRE-PAYOUT-001',
            'state' => 'CONFIRMED',
            'amount' => 20000,
            'currency' => 'MVR',
        ])
            ->assertOk()
            ->assertJson(['ok' => true, 'result' => 'paid']);

        $before = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertNotNull($before);

        $builder = app(PayoutBatchBuilder::class);
        $created = $builder->buildBatchesForDate(Carbon::today(), 1);
        $this->assertNotEmpty($created);

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'payout_status' => 'queued',
        ]);

        $this->withSession([
            'portal_customer_authenticated' => true,
            'portal_customer_email' => 'customer.prededuct@example.com',
        ])
            ->post('/customer/bookings/' . $reservationId . '/cancel')
            ->assertRedirect('/customer');

        $refundCase = DB::table('finance_refund_cases')
            ->where('reservation_id', $reservationId)
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($refundCase);

        $router = new RefundRouter(new LedgerWriter());
        $router->approveCase((string) $refundCase->case_ref, 1);
        $router->completeCase((string) $refundCase->case_ref, 'REFUND-PRE-001', 1);

        $after = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertNotNull($after);

        $this->assertDatabaseHas('finance_refund_cases', [
            'id' => (int) $refundCase->id,
            'status' => 'completed',
            'offset_mode' => 'pre_payout_deduction',
        ]);
        $this->assertLessThanOrEqual((float) ($before->vendor_payout_amount ?? 0), (float) ($after->vendor_payout_amount ?? 0));
        $this->assertContains((string) ($after->payout_status ?? ''), ['queued', 'cancelled']);
    }

    private function createReservationForCustomer(string $customerEmail, Carbon $collectedAt): int
    {
        $vendor = User::factory()->create();

        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'name' => 'Lifecycle Test Villa',
            'location' => 'Male',
            'status' => 'active',
            'max_guests' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vendor_accommodation_listings')
            ->where('id', $propertyId)
            ->update(['vendor_property_id' => $propertyId]);

        return (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'customer_name' => 'Lifecycle Customer',
            'customer_email' => $customerEmail,
            'start_at' => now()->addDays(5),
            'end_at' => now()->addDays(7),
            'guests' => 2,
            'total_amount' => 2000,
            'currency' => 'MVR',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_gateway' => 'bml_mvr',
            'payment_currency' => 'MVR',
            'payment_amount' => 2000,
            'payment_verified_at' => null,
            'payment_collected_at' => $collectedAt,
            'commission_amount' => 240,
            'commission_rate_percent' => 12,
            'gateway_fee_amount' => 80,
            'gateway_fee_rate_percent' => 4,
            'vendor_payout_amount' => 1680,
            'notes' => json_encode([
                'invoice_total_amount' => 2000,
                'category_key' => 'accommodation',
                'room_name' => 'Hoara duplex',
            ]),
            'created_at' => $collectedAt,
            'updated_at' => $collectedAt,
        ]);
    }
}
