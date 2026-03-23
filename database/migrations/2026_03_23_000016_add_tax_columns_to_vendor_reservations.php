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
            if (!Schema::hasColumn('vendor_reservations', 'guest_is_foreigner')) {
                $table->boolean('guest_is_foreigner')->default(true)->after('guests');
            }
            if (!Schema::hasColumn('vendor_reservations', 'green_tax_rate_per_person')) {
                $table->decimal('green_tax_rate_per_person', 8, 2)->default(0)->after('guest_is_foreigner');
            }
            if (!Schema::hasColumn('vendor_reservations', 'green_tax_total')) {
                $table->decimal('green_tax_total', 12, 2)->default(0)->after('green_tax_rate_per_person');
            }
            if (!Schema::hasColumn('vendor_reservations', 'tgst_rate_percent')) {
                $table->decimal('tgst_rate_percent', 6, 2)->default(0)->after('green_tax_total');
            }
            if (!Schema::hasColumn('vendor_reservations', 'tgst_total')) {
                $table->decimal('tgst_total', 12, 2)->default(0)->after('tgst_rate_percent');
            }
            if (!Schema::hasColumn('vendor_reservations', 'cgst_rate_percent')) {
                $table->decimal('cgst_rate_percent', 6, 2)->default(0)->after('tgst_total');
            }
            if (!Schema::hasColumn('vendor_reservations', 'cgst_total')) {
                $table->decimal('cgst_total', 12, 2)->default(0)->after('cgst_rate_percent');
            }
            if (!Schema::hasColumn('vendor_reservations', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 12, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('vendor_reservations', 'total_tax_amount')) {
                $table->decimal('total_tax_amount', 12, 2)->default(0)->after('subtotal_amount');
            }
            if (!Schema::hasColumn('vendor_reservations', 'invoice_total_amount')) {
                $table->decimal('invoice_total_amount', 12, 2)->default(0)->after('total_tax_amount');
            }
            if (!Schema::hasColumn('vendor_reservations', 'tax_breakdown_json')) {
                $table->longText('tax_breakdown_json')->nullable()->after('invoice_total_amount');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_reservations')) {
            return;
        }

        Schema::table('vendor_reservations', function (Blueprint $table): void {
            $columns = [
                'guest_is_foreigner',
                'green_tax_rate_per_person',
                'green_tax_total',
                'tgst_rate_percent',
                'tgst_total',
                'cgst_rate_percent',
                'cgst_total',
                'subtotal_amount',
                'total_tax_amount',
                'invoice_total_amount',
                'tax_breakdown_json',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('vendor_reservations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
