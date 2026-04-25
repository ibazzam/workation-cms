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

        if (!Schema::hasColumn('vendor_accommodation_listings', 'currency')) {
            return;
        }

        Schema::table('vendor_accommodation_listings', function (Blueprint $table): void {
            $table->dropColumn('currency');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_accommodation_listings')) {
            return;
        }

        if (Schema::hasColumn('vendor_accommodation_listings', 'currency')) {
            return;
        }

        Schema::table('vendor_accommodation_listings', function (Blueprint $table): void {
            $table->string('currency', 8)->default('MVR')->after('description');
        });
    }
};
