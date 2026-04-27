<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('vendor_reservations')) {
            return;
        }

        Schema::table('vendor_reservations', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_reservations', 'payment_collected_at')) {
                $table->timestamp('payment_collected_at')->nullable()->after('payment_verified_at');
            }
            if (!Schema::hasColumn('vendor_reservations', 'payout_processing_at')) {
                $table->timestamp('payout_processing_at')->nullable()->after('payout_status');
            }
            if (!Schema::hasColumn('vendor_reservations', 'payout_expected_at')) {
                $table->timestamp('payout_expected_at')->nullable()->after('payout_processing_at');
            }
            if (!Schema::hasColumn('vendor_reservations', 'payout_paid_at')) {
                $table->timestamp('payout_paid_at')->nullable()->after('payout_expected_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_reservations')) {
            return;
        }

        Schema::table('vendor_reservations', function (Blueprint $table): void {
            foreach (['payout_paid_at', 'payout_expected_at', 'payout_processing_at', 'payment_collected_at'] as $column) {
                if (Schema::hasColumn('vendor_reservations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
