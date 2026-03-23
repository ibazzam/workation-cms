<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_reservations')) {
            return;
        }

        Schema::table('vendor_reservations', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_reservations', 'service_charge_rate_percent')) {
                $table->decimal('service_charge_rate_percent', 6, 2)->default(0)->after('guest_is_foreigner');
            }
            if (!Schema::hasColumn('vendor_reservations', 'service_charge_total')) {
                $table->decimal('service_charge_total', 12, 2)->default(0)->after('service_charge_rate_percent');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_reservations')) {
            return;
        }

        Schema::table('vendor_reservations', function (Blueprint $table): void {
            foreach (['service_charge_rate_percent', 'service_charge_total'] as $column) {
                if (Schema::hasColumn('vendor_reservations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
