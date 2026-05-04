<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_services')) {
            return;
        }

        Schema::table('vendor_services', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_services', 'price_local')) {
                $table->decimal('price_local', 12, 2)->nullable()->after('price');
            }

            if (!Schema::hasColumn('vendor_services', 'price_usd')) {
                $table->decimal('price_usd', 12, 2)->nullable()->after('price_local');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_services')) {
            return;
        }

        Schema::table('vendor_services', function (Blueprint $table): void {
            if (Schema::hasColumn('vendor_services', 'price_usd')) {
                $table->dropColumn('price_usd');
            }

            if (Schema::hasColumn('vendor_services', 'price_local')) {
                $table->dropColumn('price_local');
            }
        });
    }
};
