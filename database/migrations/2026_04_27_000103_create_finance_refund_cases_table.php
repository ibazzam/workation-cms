<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finance Refund Cases.
 *
 * Tracks the full lifecycle of a customer refund request.
 * Refunds must be routed back through the SAME medium the payment was collected
 * through (mib → mib, bml → bml, stripe → stripe).
 *
 * source_medium is stored here for routing purposes but is INTERNAL ONLY
 * and must never appear in vendor-facing views.
 *
 * Possible statuses:
 *   requested → under_review → approved → processing → completed | rejected
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_refund_cases')) {
            return;
        }

        Schema::create('finance_refund_cases', function (Blueprint $table): void {
            $table->id();

            $table->string('case_ref', 64)->unique();   // e.g. RFND-20260427-0001
            $table->unsignedBigInteger('reservation_id');
            $table->unsignedBigInteger('vendor_user_id');
            $table->unsignedBigInteger('customer_user_id')->nullable();

            // ── Original payment details – used to route the refund ───────────────
            $table->string('original_gateway', 40)->nullable();
            $table->string('original_payment_reference', 160)->nullable();
            $table->decimal('original_amount', 14, 4);
            $table->string('original_currency', 8);

            // ── INTERNAL: which medium the refund must flow back through ──────────
            $table->string('source_medium', 16)->nullable(); // mib | bml | stripe
            $table->string('currency_band', 16)->nullable(); // local_mvr | foreign_usd

            // ── Refund amount ─────────────────────────────────────────────────────
            $table->decimal('refund_amount', 14, 4);
            $table->string('refund_currency', 8);
            $table->string('refund_type', 24)->default('full'); // full | partial

            // ── Reason ────────────────────────────────────────────────────────────
            $table->string('reason_code', 40)->nullable();
            $table->text('reason_notes')->nullable();
            $table->string('requested_by_role', 40)->nullable();
            $table->unsignedBigInteger('requested_by_user_id')->nullable();

            // ── Lifecycle ─────────────────────────────────────────────────────────
            $table->string('status', 30)->default('requested');
            $table->string('gateway_refund_reference', 160)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $table->unsignedBigInteger('processed_by_user_id')->nullable();
            $table->text('resolution_notes')->nullable();

            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────────────────────
            $table->index(['status', 'created_at']);
            $table->index(['reservation_id']);
            $table->index(['vendor_user_id', 'status']);
            $table->index(['source_medium', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_refund_cases');
    }
};
