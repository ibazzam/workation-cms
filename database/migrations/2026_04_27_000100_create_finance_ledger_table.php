<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finance Ledger – immutable event log.
 *
 * Every financial event (collection, commission, gateway fee, payout, refund, dispute)
 * is appended here.  Rows are never updated or deleted.  To reverse an event write a
 * new row with a reversal event_type and reference_ledger_id pointing to the original.
 *
 * source_medium (mib | bml | stripe) is INTERNAL ONLY and must NEVER be surfaced in
 * any vendor-facing view or API response.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_ledger')) {
            return;
        }

        Schema::create('finance_ledger', function (Blueprint $table): void {
            $table->id();

            // ── Event classification ──────────────────────────────────────────────
            // Allowed event_type values:
            //   payment_collected | commission_deducted | gateway_fee_deducted
            //   vendor_payout_queued | vendor_payout_sent | vendor_payout_confirmed
            //   refund_initiated | refund_completed
            //   dispute_opened | dispute_resolved | dispute_lost
            $table->string('event_type', 40);

            // ── Parties ───────────────────────────────────────────────────────────
            $table->unsignedBigInteger('reservation_id')->nullable(); // vendor_reservations.id
            $table->unsignedBigInteger('vendor_user_id')->nullable(); // users.id (vendor)
            $table->unsignedBigInteger('customer_user_id')->nullable(); // users.id (customer)

            // ── Money ─────────────────────────────────────────────────────────────
            $table->decimal('amount', 14, 4);             // signed; negative = deduction/refund
            $table->string('currency', 8);                 // MVR or USD

            // ── Payment source – INTERNAL ONLY, never shown to vendors ────────────
            // medium: mib | bml | stripe
            // currency_band: local_mvr | foreign_usd
            $table->string('source_medium', 16)->nullable();
            $table->string('currency_band', 16)->nullable();

            // ── Traceability ──────────────────────────────────────────────────────
            $table->string('gateway_reference', 160)->nullable(); // intent/charge ID from gateway
            $table->string('batch_id', 64)->nullable();           // payout batch this belongs to
            $table->unsignedBigInteger('reference_ledger_id')->nullable(); // for reversals
            $table->string('actor_role', 40)->nullable();          // system | ADMIN | ADMIN_FINANCE
            $table->unsignedBigInteger('actor_user_id')->nullable();

            // ── Snapshot at event time (denormalised for audit) ───────────────────
            $table->decimal('commission_rate_pct', 8, 4)->nullable();
            $table->decimal('gateway_fee_rate_pct', 8, 4)->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('occurred_at')->useCurrent();
            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────────────────────
            $table->index(['event_type', 'occurred_at']);
            $table->index(['reservation_id', 'event_type']);
            $table->index(['vendor_user_id', 'occurred_at']);
            $table->index(['source_medium', 'currency_band', 'occurred_at']);
            $table->index(['batch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_ledger');
    }
};
