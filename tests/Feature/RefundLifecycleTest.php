<?php

namespace Tests\Feature;

use App\Finance\LedgerWriter;
use App\Finance\RefundRouter;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * RefundLifecycleTest
 *
 * Comprehensive test suite for refund case lifecycle management including:
 *  - Refund timeline state transitions
 *  - Pre-payout deduction path (refund completes before payout settles)
 *  - Post-payout receivable/on-hold path (refund completes after payout settles)
 *  - SLA escalation tracking and markers
 */
class RefundLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private RefundRouter $refundRouter;
    private LedgerWriter $ledger;

    protected function setUp(): void
    {
        parent::setUp();

        if (!DB::connection()->getSchemaBuilder()->hasTable('finance_refund_cases')) {
            $this->migrate();
        }

        $this->ledger = app(LedgerWriter::class);
        $this->refundRouter = new RefundRouter($this->ledger);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // REFUND TIMELINE TRANSITIONS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Test: Refund case transitions through full timeline
     * requested → under_review → approved → completed
     */
    public function test_refund_transitions_through_full_timeline(): void
    {
        $reservation = $this->createTestReservation();

        // 1. Open case (status: requested)
        $caseRef = $this->refundRouter->openCase([
            'reservation_id'    => $reservation->id,
            'vendor_user_id'    => $reservation->vendor_user_id,
            'customer_user_id'  => 1,
            'refund_amount'     => 50.00,
            'refund_type'       => 'full',
            'reason_code'       => 'customer_request',
            'requested_by_role' => 'ADMIN',
            'requested_by_user_id' => 2,
        ]);

        $case = DB::table('finance_refund_cases')->where('case_ref', $caseRef)->first();
        $this->assertNotNull($case);
        $this->assertSame('requested', (string) $case->status);
        $this->assertNull($case->review_started_at ?? null);
        $this->assertNull($case->approved_at ?? null);
        $this->assertNull($case->completed_at ?? null);

        // 2. Start review (status: under_review)
        $this->refundRouter->startReview($caseRef, 3);
        $case = DB::table('finance_refund_cases')->where('case_ref', $caseRef)->first();
        $this->assertSame('under_review', (string) $case->status);
        $this->assertNotNull($case->review_started_at ?? null);

        // 3. Approve case (status: approved)
        $this->refundRouter->approveCase($caseRef, 3);
        $case = DB::table('finance_refund_cases')->where('case_ref', $caseRef)->first();
        $this->assertSame('approved', (string) $case->status);
        $this->assertNotNull($case->approved_at ?? null);

        // 4. Complete case (status: completed)
        $this->refundRouter->completeCase($caseRef, 'stripe-refund-ch_12345', 3);
        $case = DB::table('finance_refund_cases')->where('case_ref', $caseRef)->first();
        $this->assertSame('completed', (string) $case->status);
        $this->assertNotNull($case->completed_at ?? null);
        $this->assertSame('stripe-refund-ch_12345', (string) $case->gateway_refund_reference);
    }

    /**
     * Test: Refund case can be rejected after review
     * requested → under_review → rejected
     */
    public function test_refund_can_be_rejected_after_review(): void
    {
        $reservation = $this->createTestReservation();

        $caseRef = $this->refundRouter->openCase([
            'reservation_id'   => $reservation->id,
            'vendor_user_id'   => $reservation->vendor_user_id,
            'customer_user_id' => 1,
            'refund_amount'    => 50.00,
        ]);

        $this->refundRouter->startReview($caseRef, 3);

        // Reject case
        $this->refundRouter->rejectCase($caseRef, 'Non-refundable booking period', 3);

        $case = DB::table('finance_refund_cases')->where('case_ref', $caseRef)->first();
        $this->assertSame('rejected', (string) $case->status);
        $this->assertNotNull($case->rejected_at ?? null);
        $this->assertSame('Non-refundable booking period', (string) $case->resolution_notes);
    }

    /**
     * Test: Refund case transitions from requested directly to rejected
     */
    public function test_refund_can_be_rejected_without_review(): void
    {
        $reservation = $this->createTestReservation();

        $caseRef = $this->refundRouter->openCase([
            'reservation_id'   => $reservation->id,
            'vendor_user_id'   => $reservation->vendor_user_id,
            'customer_user_id' => 1,
            'refund_amount'    => 50.00,
        ]);

        // Reject without review
        $this->refundRouter->rejectCase($caseRef, 'Invalid request', 3);

        $case = DB::table('finance_refund_cases')->where('case_ref', $caseRef)->first();
        $this->assertSame('rejected', (string) $case->status);
        $this->assertNotNull($case->rejected_at ?? null);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PRE-PAYOUT DEDUCTION PATH
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Test: Pre-payout deduction when payout_status is 'queued'
     * (Refund completes before payout is settled)
     */
    public function test_pre_payout_deduction_when_payout_queued(): void
    {
        $vendor = User::factory()->create();

        // Create reservation with queued payout
        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => 0,
            'name' => 'Test Property',
            'location' => 'Male',
            'status' => 'active',
            'max_guests' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vendor_accommodation_listings')->where('id', $propertyId)->update(['vendor_property_id' => $propertyId]);

        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'payment_gateway' => 'stripe',
            'payment_currency' => 'USD',
            'payment_reference' => 'pi_1234567890',
            'payment_amount' => 1000.00,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(3),
            'guests' => 2,
            'total_amount' => 1000.00,
            'currency' => 'USD',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'vendor_payout_amount' => 800.00,
            'payout_status' => 'queued',
            'payout_paid_at' => null,
            'notes' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reservation = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertSame(800.00, (float) $reservation->vendor_payout_amount);
        $this->assertSame('queued', (string) $reservation->payout_status);

        // Open and complete refund (400.00)
        $caseRef = $this->refundRouter->openCase([
            'reservation_id'   => $reservationId,
            'vendor_user_id'   => $vendor->id,
            'customer_user_id' => 1,
            'refund_amount'    => 400.00,
        ]);

        $this->refundRouter->approveCase($caseRef, 3);
        $this->refundRouter->completeCase($caseRef, 'stripe-refund-ch_test', 3);

        // Verify pre-payout deduction was applied
        $case = DB::table('finance_refund_cases')->where('case_ref', $caseRef)->first();
        $this->assertSame('pre_payout_deduction', (string) $case->offset_mode);
        $this->assertSame(400.00, (float) $case->offset_amount);
        $this->assertNotNull($case->offset_applied_at ?? null);

        // Verify reservation payout was reduced
        $updatedReservation = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertSame(400.00, (float) $updatedReservation->vendor_payout_amount);
        $this->assertSame('queued', (string) $updatedReservation->payout_status);
    }

    /**
     * Test: Pre-payout deduction cancels payout when refund equals full payout
     */
    public function test_pre_payout_deduction_cancels_payout_when_full_refund(): void
    {
        $vendor = User::factory()->create();
        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => 0,
            'name' => 'Test Property',
            'location' => 'Male',
            'status' => 'active',
            'max_guests' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vendor_accommodation_listings')->where('id', $propertyId)->update(['vendor_property_id' => $propertyId]);

        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'payment_gateway' => 'stripe',
            'payment_currency' => 'USD',
            'payment_reference' => 'pi_1234567890',
            'payment_amount' => 1000.00,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(3),
            'guests' => 2,
            'total_amount' => 1000.00,
            'currency' => 'USD',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'vendor_payout_amount' => 800.00,
            'payout_status' => 'queued',
            'payout_paid_at' => null,
            'notes' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Full refund of payout
        $caseRef = $this->refundRouter->openCase([
            'reservation_id'   => $reservationId,
            'vendor_user_id'   => $vendor->id,
            'customer_user_id' => 1,
            'refund_amount'    => 800.00,
        ]);

        $this->refundRouter->approveCase($caseRef, 3);
        $this->refundRouter->completeCase($caseRef, 'stripe-refund-ch_test', 3);

        // Verify payout was cancelled
        $updatedReservation = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertSame(0.00, (float) $updatedReservation->vendor_payout_amount);
        $this->assertSame('cancelled', (string) $updatedReservation->payout_status);
    }

    /**
     * Test: Pre-payout deduction when payout is in 'processing' state
     */
    public function test_pre_payout_deduction_when_payout_processing(): void
    {
        $vendor = User::factory()->create();
        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => 0,
            'name' => 'Test Property',
            'location' => 'Male',
            'status' => 'active',
            'max_guests' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vendor_accommodation_listings')->where('id', $propertyId)->update(['vendor_property_id' => $propertyId]);

        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'payment_gateway' => 'stripe',
            'payment_currency' => 'USD',
            'payment_reference' => 'pi_1234567890',
            'payment_amount' => 1000.00,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(3),
            'guests' => 2,
            'total_amount' => 1000.00,
            'currency' => 'USD',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'vendor_payout_amount' => 800.00,
            'payout_status' => 'processing',
            'payout_paid_at' => null,
            'notes' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $caseRef = $this->refundRouter->openCase([
            'reservation_id'   => $reservationId,
            'vendor_user_id'   => $vendor->id,
            'customer_user_id' => 1,
            'refund_amount'    => 300.00,
        ]);

        $this->refundRouter->approveCase($caseRef, 3);
        $this->refundRouter->completeCase($caseRef, 'stripe-refund-ch_test', 3);

        // Verify pre-payout deduction applied to processing payout
        $case = DB::table('finance_refund_cases')->where('case_ref', $caseRef)->first();
        $this->assertSame('pre_payout_deduction', (string) $case->offset_mode);

        $updatedReservation = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertSame(500.00, (float) $updatedReservation->vendor_payout_amount);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST-PAYOUT RECEIVABLE / ON-HOLD PATH
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Test: Post-payout receivable when payout already paid
     * (Refund completes after payout has settled)
     */
    public function test_post_payout_receivable_when_payout_paid(): void
    {
        $vendor = User::factory()->create();
        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => 0,
            'name' => 'Test Property',
            'location' => 'Male',
            'status' => 'active',
            'max_guests' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vendor_accommodation_listings')->where('id', $propertyId)->update(['vendor_property_id' => $propertyId]);

        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'payment_gateway' => 'stripe',
            'payment_currency' => 'USD',
            'payment_reference' => 'pi_1234567890',
            'payment_amount' => 1000.00,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(3),
            'guests' => 2,
            'total_amount' => 1000.00,
            'currency' => 'USD',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'vendor_payout_amount' => 800.00,
            'payout_status' => 'paid',
            'payout_paid_at' => now()->subHours(2),
            'notes' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $caseRef = $this->refundRouter->openCase([
            'reservation_id'   => $reservationId,
            'vendor_user_id'   => $vendor->id,
            'customer_user_id' => 1,
            'refund_amount'    => 300.00,
        ]);

        $this->refundRouter->approveCase($caseRef, 3);
        $this->refundRouter->completeCase($caseRef, 'stripe-refund-ch_test', 3);

        // Verify post-payout receivable mode applied
        $case = DB::table('finance_refund_cases')->where('case_ref', $caseRef)->first();
        $this->assertSame('post_payout_receivable', (string) $case->offset_mode);
        $this->assertSame(300.00, (float) $case->offset_amount);

        // Verify reservation payout_status is marked on_hold
        $updatedReservation = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertSame('on_hold', (string) $updatedReservation->payout_status);
        $this->assertSame(800.00, (float) $updatedReservation->vendor_payout_amount); // Amount NOT reduced
    }

    /**
     * Test: Post-payout receivable when payout_paid_at is set
     */
    public function test_post_payout_receivable_when_payout_paid_at_set(): void
    {
        $vendor = User::factory()->create();
        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => 0,
            'name' => 'Test Property',
            'location' => 'Male',
            'status' => 'active',
            'max_guests' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vendor_accommodation_listings')->where('id', $propertyId)->update(['vendor_property_id' => $propertyId]);

        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'customer_name' => 'Test Customer',
            'customer_email' => 'customer@example.com',
            'payment_gateway' => 'stripe',
            'payment_currency' => 'USD',
            'payment_reference' => 'pi_1234567890',
            'payment_amount' => 1000.00,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(3),
            'guests' => 2,
            'total_amount' => 1000.00,
            'currency' => 'USD',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'vendor_payout_amount' => 800.00,
            'payout_status' => 'queued',
            'payout_paid_at' => now()->subHours(5),
            'notes' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $caseRef = $this->refundRouter->openCase([
            'reservation_id'   => $reservationId,
            'vendor_user_id'   => $vendor->id,
            'customer_user_id' => 1,
            'refund_amount'    => 200.00,
        ]);

        $this->refundRouter->approveCase($caseRef, 3);
        $this->refundRouter->completeCase($caseRef, 'stripe-refund-ch_test', 3);

        // Verify post-payout mode when payout_paid_at is set (even if status not 'paid')
        $case = DB::table('finance_refund_cases')->where('case_ref', $caseRef)->first();
        $this->assertSame('post_payout_receivable', (string) $case->offset_mode);

        $updatedReservation = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertSame('on_hold', (string) $updatedReservation->payout_status);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SLA ESCALATION TRANSITIONS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Test: SLA due date is set to 48 hours on case creation
     */
    public function test_sla_due_date_set_on_case_creation(): void
    {
        $reservation = $this->createTestReservation();
        $now = Carbon::now();

        $caseRef = $this->refundRouter->openCase([
            'reservation_id'   => $reservation->id,
            'vendor_user_id'   => $reservation->vendor_user_id,
            'customer_user_id' => 1,
            'refund_amount'    => 50.00,
        ]);

        $case = DB::table('finance_refund_cases')->where('case_ref', $caseRef)->first();
        $this->assertNotNull($case->sla_due_at ?? null);

        // SLA due should be ~48 hours from now
        $slaDue = Carbon::parse($case->sla_due_at);
        $diff = $now->diffInHours($slaDue);
        $this->assertGreaterThanOrEqual(47, $diff);
        $this->assertLessThanOrEqual(49, $diff);
    }

    /**
     * Test: SLA escalation marker can be set manually
     */
    public function test_sla_escalation_marker_can_be_set(): void
    {
        $reservation = $this->createTestReservation();

        $caseRef = $this->refundRouter->openCase([
            'reservation_id'   => $reservation->id,
            'vendor_user_id'   => $reservation->vendor_user_id,
            'customer_user_id' => 1,
            'refund_amount'    => 50.00,
        ]);

        $case = DB::table('finance_refund_cases')->where('case_ref', $caseRef)->first();
        $this->assertNull($case->sla_escalated_at ?? null);

        // Manually mark as escalated
        $now = Carbon::now();
        DB::table('finance_refund_cases')
            ->where('case_ref', $caseRef)
            ->update(['sla_escalated_at' => $now]);

        $updatedCase = DB::table('finance_refund_cases')->where('case_ref', $caseRef)->first();
        $this->assertNotNull($updatedCase->sla_escalated_at ?? null);
    }

    /**
     * Test: Multiple refund cases on same reservation don't clear flag prematurely
     */
    public function test_has_refund_case_flag_with_multiple_cases(): void
    {
        $reservation = $this->createTestReservation();
        $reservationId = $reservation->id;

        // Open first case
        $caseRef1 = $this->refundRouter->openCase([
            'reservation_id'   => $reservationId,
            'vendor_user_id'   => $reservation->vendor_user_id,
            'customer_user_id' => 1,
            'refund_amount'    => 30.00,
        ]);

        $res = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertTrue((bool) $res->has_refund_case);

        // Open second case
        $caseRef2 = $this->refundRouter->openCase([
            'reservation_id'   => $reservationId,
            'vendor_user_id'   => $reservation->vendor_user_id,
            'customer_user_id' => 1,
            'refund_amount'    => 20.00,
        ]);

        // Complete first case
        $this->refundRouter->approveCase($caseRef1, 3);
        $this->refundRouter->completeCase($caseRef1, 'stripe-refund-ch_1', 3);

        // Reservation should still have has_refund_case = true (because case 2 is open)
        $res = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertTrue((bool) $res->has_refund_case);

        // Complete second case
        $this->refundRouter->approveCase($caseRef2, 3);
        $this->refundRouter->completeCase($caseRef2, 'stripe-refund-ch_2', 3);

        // Now has_refund_case should be false (all cases closed)
        $res = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertFalse((bool) $res->has_refund_case);
    }

    /**
     * Test: Rejecting all cases clears the refund_case flag
     */
    public function test_has_refund_case_flag_cleared_on_all_rejections(): void
    {
        $reservation = $this->createTestReservation();
        $reservationId = $reservation->id;

        $caseRef = $this->refundRouter->openCase([
            'reservation_id'   => $reservationId,
            'vendor_user_id'   => $reservation->vendor_user_id,
            'customer_user_id' => 1,
            'refund_amount'    => 50.00,
        ]);

        $res = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertTrue((bool) $res->has_refund_case);

        // Reject case
        $this->refundRouter->rejectCase($caseRef, 'Not eligible', 3);

        // Flag should be cleared
        $res = DB::table('vendor_reservations')->where('id', $reservationId)->first();
        $this->assertFalse((bool) $res->has_refund_case);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // LEDGER AUDIT TRAIL
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Test: Ledger events are written for refund lifecycle
     */
    public function test_ledger_events_written_for_refund_lifecycle(): void
    {
        $reservation = $this->createTestReservation();

        $caseRef = $this->refundRouter->openCase([
            'reservation_id'   => $reservation->id,
            'vendor_user_id'   => $reservation->vendor_user_id,
            'customer_user_id' => 1,
            'refund_amount'    => 50.00,
            'requested_by_role' => 'ADMIN',
            'requested_by_user_id' => 2,
        ]);

        // Verify REFUND_INITIATED event exists
        if (DB::getSchemaBuilder()->hasTable('finance_ledger')) {
            $events = DB::table('finance_ledger')
                ->where('event_type', LedgerWriter::EVT_REFUND_INITIATED)
                ->where('notes', 'like', '%' . $caseRef . '%')
                ->count();
            $this->assertGreaterThan(0, $events, 'REFUND_INITIATED ledger event should exist');
        }

        $this->refundRouter->approveCase($caseRef, 3);
        $this->refundRouter->completeCase($caseRef, 'stripe-refund-ch_test', 3);

        // Verify REFUND_COMPLETED event exists
        if (DB::getSchemaBuilder()->hasTable('finance_ledger')) {
            $events = DB::table('finance_ledger')
                ->where('event_type', LedgerWriter::EVT_REFUND_COMPLETED)
                ->where('notes', 'like', '%' . $caseRef . '%')
                ->count();
            $this->assertGreaterThan(0, $events, 'REFUND_COMPLETED ledger event should exist');
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // HELPER METHODS
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Create a test reservation for refund lifecycle tests
     */
    private function createTestReservation()
    {
        $vendor = User::factory()->create();

        $propertyId = (int) DB::table('vendor_accommodation_listings')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => 0,
            'name' => 'Refund Test Property',
            'location' => 'Male',
            'status' => 'active',
            'max_guests' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('vendor_accommodation_listings')->where('id', $propertyId)->update(['vendor_property_id' => $propertyId]);

        $reservationId = (int) DB::table('vendor_reservations')->insertGetId([
            'vendor_user_id' => $vendor->id,
            'vendor_property_id' => $propertyId,
            'customer_name' => 'Refund Test Customer',
            'customer_email' => 'refund-test@example.com',
            'payment_gateway' => 'stripe',
            'payment_currency' => 'USD',
            'payment_reference' => 'pi_test_' . uniqid(),
            'payment_amount' => 500.00,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDays(3),
            'guests' => 2,
            'total_amount' => 500.00,
            'currency' => 'USD',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'notes' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('vendor_reservations')->where('id', $reservationId)->first();
    }
}
