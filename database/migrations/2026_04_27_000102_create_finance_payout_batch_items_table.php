<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finance Payout Batch Items.
 *
 * Each row is one vendor's payout within a single payout batch.
 * Multiple reservations for the same vendor within the same batch are rolled up
 * into one item row; the raw per-reservation breakdown lives in finance_ledger.
 *
 * INTERNAL ONLY – batch_id/source_medium links must never be surfaced to vendors.
 * Vendors see only: net_payout_amount, currency, and payout_status from
 * the vendor_reservations table (updated when this item is confirmed).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_payout_batch_items')) {
            return;
        }

        Schema::create('finance_payout_batch_items', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('batch_id');       // finance_payout_batches.id
            $table->unsignedBigInteger('vendor_user_id'); // users.id (vendor)

            // ── Reservation coverage ──────────────────────────────────────────────
            // JSON array of vendor_reservations.id values included in this item
            $table->json('reservation_ids');

            // ── Money ─────────────────────────────────────────────────────────────
            $table->decimal('gross_amount', 14, 4)->default(0);
            $table->decimal('commission_amount', 14, 4)->default(0);
            $table->decimal('gateway_fee_amount', 14, 4)->default(0);
            $table->decimal('net_payout_amount', 14, 4)->default(0);
            $table->string('currency', 8);

            // ── Vendor bank details snapshot at time of payout ────────────────────
            $table->string('bank_account_name', 160)->nullable();
            $table->string('bank_account_number', 80)->nullable();
            $table->string('bank_name', 80)->nullable();

            // ── Lifecycle ─────────────────────────────────────────────────────────
            // queued → sent → confirmed | failed
            $table->string('status', 24)->default('queued');
            $table->string('bank_reference', 160)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // ── Constraints ───────────────────────────────────────────────────────
            $table->foreign('batch_id')
                ->references('id')
                ->on('finance_payout_batches')
                ->cascadeOnDelete();

            $table->index(['batch_id', 'vendor_user_id']);
            $table->index(['vendor_user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payout_batch_items');
    }
};
