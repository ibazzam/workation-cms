<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('vendor_liveaboard_listings')) {
            return;
        }

        Schema::table('vendor_liveaboard_listings', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_liveaboard_listings', 'vendor_property_id')) {
                $table->unsignedBigInteger('vendor_property_id')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('vendor_liveaboard_listings')) {
            return;
        }

        Schema::table('vendor_liveaboard_listings', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_liveaboard_listings', 'vendor_property_id')) {
                $table->unsignedBigInteger('vendor_property_id')->nullable(false)->change();
            }
        });
    }
};
