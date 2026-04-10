<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add island_type to islands table.
     * Values: 'inhabited' | 'resort' | 'uninhabited' | null (unknown)
     * 'resort' = tourist resort island (typically uninhabited but with resort development)
     */
    public function up(): void
    {
        Schema::table('islands', function (Blueprint $table) {
            if (!Schema::hasColumn('islands', 'island_type')) {
                $table->string('island_type', 30)->nullable()->after('is_inhabited');
            }
        });
    }

    public function down(): void
    {
        Schema::table('islands', function (Blueprint $table) {
            if (Schema::hasColumn('islands', 'island_type')) {
                $table->dropColumn('island_type');
            }
        });
    }
};
