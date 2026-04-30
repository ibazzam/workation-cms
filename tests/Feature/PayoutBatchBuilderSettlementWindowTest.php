<?php

namespace Tests\Feature;

use App\Finance\PayoutBatchBuilder;
use App\Models\User;
use App\Support\ReservationSettlementCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PayoutBatchBuilderSettlementWindowTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_reservation_is_not_batched_before_settlement_window_elapses(): void
    {
        $reservationId = $this->createPaidReservation('stripe', 'USD', Carbon::parse('2026-04-01 09:00:00'));
        $readyAt = ReservationSettlementCalculator::expectedPayoutAt('2026-04-01 09:00:00', 'stripe', 'stripe');

        $builder = app(PayoutBatchBuilder::class);

        $batches = $builder->buildBatchesForDate($readyAt->copy()->subDay(), 1);

        $this->assertSame([], $batches);
        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'payout_status' => null,
        ]);
    }

    public function test_bml_reservation_can_be_batched_once_settlement_window_elapses(): void
    {
        $reservationId = $this->createPaidReservation('bml_mvr', 'MVR', Carbon::parse('2026-04-01 09:00:00'));
        $readyAt = ReservationSettlementCalculator::expectedPayoutAt('2026-04-01 09:00:00', 'bml_mvr', 'bml');

        $builder = app(PayoutBatchBuilder::class);

        $batches = $builder->buildBatchesForDate($readyAt, 1);

        $this->assertNotEmpty($batches);
        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $reservationId,
            'payout_status' => 'queued',
        ]);
        $this->assertDatabaseCount('finance_payout_batches', 1);
    }

    public function test_same_vendor_same_currency_can_be_combined_across_gateways(): void
    {
        $vendor = User::factory()->create();

        $firstCollectedAt = Carbon::parse('2026-04-01 09:00:00');
        $secondCollectedAt = Carbon::parse('2026-04-02 09:00:00');

        $firstReservationId = $this->createPaidReservation('bml_mvr', 'MVR', $firstCollectedAt, $vendor);
        $secondReservationId = $this->createPaidReservation('mib_mvr', 'MVR', $secondCollectedAt, $vendor);

        $readyAt = ReservationSettlementCalculator::expectedPayoutAt('2026-04-02 09:00:00', 'mib_mvr', 'mib');
        $builder = app(PayoutBatchBuilder::class);

        $batches = $builder->buildBatchesForDate($readyAt, 1, true);

        $this->assertCount(1, $batches);
        $this->assertDatabaseCount('finance_payout_batches', 1);
        $this->assertDatabaseHas('finance_payout_batches', [
            'source_medium' => 'mixed',
            'currency_band' => 'local_mvr',
            'currency' => 'MVR',
            'item_count' => 1,
        ]);

        $batchItem = DB::table('finance_payout_batch_items')->first();
        $this->assertNotNull($batchItem);
        $reservationIds = collect(json_decode((string) $batchItem->reservation_ids, true))->map(static fn ($id): int => (int) $id)->sort()->values()->all();
        $this->assertSame([
            min($firstReservationId, $secondReservationId),
            max($firstReservationId, $secondReservationId),
        ], $reservationIds);

        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $firstReservationId,
            'payout_status' => 'queued',
            'payout_source_medium' => 'mixed',
        ]);
        $this->assertDatabaseHas('vendor_reservations', [
            'id' => $secondReservationId,
            'payout_status' => 'queued',
            'payout_source_medium' => 'mixed',
        ]);
    }

    private function createPaidReservation(string $gateway, string $currency, Carbon $collectedAt, ?User $vendor = null): int
    {
        $vendor ??= User::factory()->create();

        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'name' => 'Settlement Window Test Property',
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
            'customer_name' => 'Settlement Guest',
            'customer_email' => 'settlement@example.com',
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(4),
            'guests' => 2,
            'total_amount' => 1500,
            'currency' => $currency,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_gateway' => $gateway,
            'payment_currency' => $currency,
            'payment_amount' => 1500,
            'payment_verified_at' => $collectedAt,
            'payment_collected_at' => $collectedAt,
            'commission_amount' => 180,
            'commission_rate_percent' => 12,
            'gateway_fee_amount' => 60,
            'gateway_fee_rate_percent' => 4,
            'vendor_payout_amount' => 1260,
            'notes' => json_encode(['invoice_total_amount' => 1500]),
            'created_at' => $collectedAt,
            'updated_at' => $collectedAt,
        ]);
    }
}