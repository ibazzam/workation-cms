<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_accommodation_listings')) {
            return;
        }

        if (!Schema::hasColumn('vendor_accommodation_listings', 'base_price')) {
            return;
        }

        Schema::table('vendor_accommodation_listings', function (Blueprint $table): void {
            $table->dropColumn('base_price');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_accommodation_listings')) {
            return;
        }

        if (Schema::hasColumn('vendor_accommodation_listings', 'base_price')) {
            return;
        }

        Schema::table('vendor_accommodation_listings', function (Blueprint $table): void {
            $table->decimal('base_price', 12, 2)->default(0)->after('description');
        });
    }
};
