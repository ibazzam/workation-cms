<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finance Dispute Cases.
 *
 * Tracks chargebacks and payment disputes.
 * Stripe disputes are the primary concern given it processes foreign-bank USD
 * payments with formal chargeback mechanics.
 * MIB/BML disputes are handled as manual cases.
 *
 * source_medium is INTERNAL ONLY – must never appear in vendor-facing views.
 *
 * Possible statuses:
 *   opened → evidence_submitted → under_review → won | lost | accepted
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_dispute_cases')) {
            return;
        }

        Schema::create('finance_dispute_cases', function (Blueprint $table): void {
            $table->id();

            $table->string('case_ref', 64)->unique();   // e.g. DISP-20260427-0001
            $table->unsignedBigInteger('reservation_id');
            $table->unsignedBigInteger('vendor_user_id');
            $table->unsignedBigInteger('customer_user_id')->nullable();

            // ── Disputed payment ──────────────────────────────────────────────────
            $table->string('gateway_dispute_id', 160)->nullable(); // e.g. Stripe dispute ID
            $table->string('original_payment_reference', 160)->nullable();
            $table->decimal('disputed_amount', 14, 4);
            $table->string('disputed_currency', 8);

            // ── INTERNAL: payment medium (never shown to vendor) ──────────────────
            $table->string('source_medium', 16)->nullable(); // mib | bml | stripe
            $table->string('currency_band', 16)->nullable(); // local_mvr | foreign_usd

            // ── Dispute classification ────────────────────────────────────────────
            $table->string('dispute_reason', 60)->nullable(); // fraudulent | duplicate | etc.
            $table->date('respond_by')->nullable();

            // ── Lifecycle ─────────────────────────────────────────────────────────
            $table->string('status', 30)->default('opened');
            $table->text('evidence_notes')->nullable();
            $table->timestamp('evidence_submitted_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('assigned_to_user_id')->nullable();
            $table->unsignedBigInteger('resolved_by_user_id')->nullable();
            $table->text('resolution_notes')->nullable();

            // ── Financial impact ──────────────────────────────────────────────────
            // If lost: funds debited; if won: funds retained.
            $table->string('outcome', 20)->nullable(); // won | lost | accepted
            $table->decimal('outcome_amount', 14, 4)->nullable();

            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────────────────────
            $table->index(['status', 'created_at']);
            $table->index(['reservation_id']);
            $table->index(['vendor_user_id', 'status']);
            $table->index(['source_medium', 'status']);
            $table->index(['respond_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_dispute_cases');
    }
};
