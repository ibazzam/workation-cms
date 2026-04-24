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
            if (!Schema::hasColumn('vendor_reservations', 'commission_rate_percent')) {
                $table->decimal('commission_rate_percent', 8, 4)->nullable()->after('payment_amount');
            }
            if (!Schema::hasColumn('vendor_reservations', 'commission_amount')) {
                $table->decimal('commission_amount', 12, 2)->nullable()->after('commission_rate_percent');
            }
            if (!Schema::hasColumn('vendor_reservations', 'gateway_fee_rate_percent')) {
                $table->decimal('gateway_fee_rate_percent', 8, 4)->nullable()->after('commission_amount');
            }
            if (!Schema::hasColumn('vendor_reservations', 'gateway_fee_amount')) {
                $table->decimal('gateway_fee_amount', 12, 2)->nullable()->after('gateway_fee_rate_percent');
            }
            if (!Schema::hasColumn('vendor_reservations', 'vendor_payout_amount')) {
                $table->decimal('vendor_payout_amount', 12, 2)->nullable()->after('gateway_fee_amount');
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
                'commission_rate_percent',
                'commission_amount',
                'gateway_fee_rate_percent',
                'gateway_fee_amount',
                'vendor_payout_amount',
            ] as $column) {
                if (Schema::hasColumn('vendor_reservations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
