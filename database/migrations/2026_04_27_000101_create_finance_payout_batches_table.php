<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finance Payout Batches.
 *
 * A payout batch groups multiple vendor payout items that are settled together
 * through the SAME payment medium and currency band.
 *
 * Batches are STRICTLY separated by:
 *   - source_medium  : mib | bml | stripe
 *   - currency_band  : local_mvr | foreign_usd
 *
 * This reflects the real-world operational reality:
 *   MIB (local MVR bank)   → same-day / next-day settlement, local currency
 *   BML (local MVR bank)   → same-day / next-day settlement, local currency
 *   Stripe (foreign bank)  → delayed settlement, USD, foreign account
 *
 * INTERNAL ONLY – source_medium must never be surfaced in vendor-facing views.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_payout_batches')) {
            return;
        }

        Schema::create('finance_payout_batches', function (Blueprint $table): void {
            $table->id();

            // ── Batch identity ────────────────────────────────────────────────────
            $table->string('batch_ref', 64)->unique();   // e.g. BATCH-MIB-MVR-20260427-001
            $table->date('batch_date');

            // ── Medium + currency – INTERNAL ONLY ─────────────────────────────────
            // source_medium: mib | bml | stripe
            $table->string('source_medium', 16);
            // currency_band: local_mvr | foreign_usd
            $table->string('currency_band', 16);
            $table->string('currency', 8);               // MVR or USD

            // ── Aggregates (denormalised for fast dashboard queries) ───────────────
            $table->unsignedInteger('item_count')->default(0);
            $table->decimal('gross_amount', 14, 4)->default(0);
            $table->decimal('commission_amount', 14, 4)->default(0);
            $table->decimal('gateway_fee_amount', 14, 4)->default(0);
            $table->decimal('net_payout_amount', 14, 4)->default(0);

            // ── Lifecycle status ──────────────────────────────────────────────────
            // queued → processing → sent → confirmed | failed | cancelled
            $table->string('status', 24)->default('queued');

            // ── Settlement tracking ───────────────────────────────────────────────
            $table->string('bank_reference', 160)->nullable();  // bank/gateway batch ref
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();

            // ── Actor ─────────────────────────────────────────────────────────────
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('confirmed_by_user_id')->nullable();

            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────────────────────
            $table->index(['batch_date', 'source_medium']);
            $table->index(['source_medium', 'currency_band', 'status']);
            $table->index(['status', 'batch_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payout_batches');
    }
};
