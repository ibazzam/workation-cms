<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('excursions')) {
            return;
        }

        if (!Schema::hasColumn('excursions', 'is_corporate_retreat')) {
            Schema::table('excursions', function (Blueprint $table) {
                $table->boolean('is_corporate_retreat')->default(false)->after('active')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('excursions') && Schema::hasColumn('excursions', 'is_corporate_retreat')) {
            Schema::table('excursions', function (Blueprint $table) {
                $table->dropColumn('is_corporate_retreat');
            });
        }
    }
};
