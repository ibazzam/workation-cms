<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add payout and dispute status tracking columns to vendor_reservations.
 *
 * These columns are the ONLY finance-related columns that may be surfaced
 * in vendor-facing views (payout_status and payout_currency only).
 *
 * payout_source_medium is INTERNAL ONLY – it exists here purely for
 * reconciliation queries and must NEVER be included in any vendor-facing
 * query, view, or API response.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_reservations')) {
            return;
        }

        Schema::table('vendor_reservations', function (Blueprint $table): void {
            // ── Vendor-visible payout columns ─────────────────────────────────────
            // payout_status: queued | processing | paid | on_hold | cancelled
            if (!Schema::hasColumn('vendor_reservations', 'payout_status')) {
                $table->string('payout_status', 24)->nullable()->after('vendor_payout_amount');
            }
            if (!Schema::hasColumn('vendor_reservations', 'payout_currency')) {
                $table->string('payout_currency', 8)->nullable()->after('payout_status');
            }
            if (!Schema::hasColumn('vendor_reservations', 'payout_batch_item_id')) {
                $table->unsignedBigInteger('payout_batch_item_id')->nullable()->after('payout_currency');
            }

            // ── INTERNAL ONLY – do NOT expose to vendors ──────────────────────────
            // Used for admin reconciliation and batch routing only.
            if (!Schema::hasColumn('vendor_reservations', 'payout_source_medium')) {
                $table->string('payout_source_medium', 16)->nullable()->after('payout_batch_item_id');
            }

            // ── Dispute flag (vendor-visible: they know a dispute exists) ─────────
            // Vendors are notified a dispute exists but NOT the source medium.
            if (!Schema::hasColumn('vendor_reservations', 'has_open_dispute')) {
                $table->boolean('has_open_dispute')->default(false)->after('payout_source_medium');
            }
            if (!Schema::hasColumn('vendor_reservations', 'has_refund_case')) {
                $table->boolean('has_refund_case')->default(false)->after('has_open_dispute');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_reservations')) {
            return;
        }

        Schema::table('vendor_reservations', function (Blueprint $table): void {
            foreach ([
                'payout_status',
                'payout_currency',
                'payout_batch_item_id',
                'payout_source_medium',
                'has_open_dispute',
                'has_refund_case',
            ] as $column) {
                if (Schema::hasColumn('vendor_reservations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
