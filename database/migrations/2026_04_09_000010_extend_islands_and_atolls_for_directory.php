<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend atolls table with slug + description
        Schema::table('atolls', function (Blueprint $table) {
            if (!Schema::hasColumn('atolls', 'slug')) {
                $table->string('slug', 190)->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('atolls', 'photo_path')) {
                $table->string('photo_path', 2048)->nullable()->after('slug');
            }
            if (!Schema::hasColumn('atolls', 'description')) {
                $table->text('description')->nullable()->after('photo_path');
            }
        });

        // Extend islands table with directory fields
        Schema::table('islands', function (Blueprint $table) {
            if (!Schema::hasColumn('islands', 'slug')) {
                $table->string('slug', 190)->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('islands', 'local_name')) {
                $table->string('local_name', 190)->nullable()->after('slug');
            }
            if (!Schema::hasColumn('islands', 'photo_path')) {
                $table->string('photo_path', 2048)->nullable()->after('local_name');
            }
            if (!Schema::hasColumn('islands', 'description')) {
                $table->text('description')->nullable()->after('photo_path');
            }
            if (!Schema::hasColumn('islands', 'population')) {
                $table->unsignedInteger('population')->nullable()->after('description');
            }
            if (!Schema::hasColumn('islands', 'nearest_airport_name')) {
                $table->string('nearest_airport_name', 190)->nullable()->after('population');
            }
            if (!Schema::hasColumn('islands', 'distance_from_airport_km')) {
                $table->unsignedSmallInteger('distance_from_airport_km')->nullable()->after('nearest_airport_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('atolls', function (Blueprint $table) {
            $table->dropColumn(array_filter(['slug', 'photo_path', 'description'], fn ($c) => Schema::hasColumn('atolls', $c)));
        });

        Schema::table('islands', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                'slug', 'local_name', 'photo_path', 'description',
                'population', 'nearest_airport_name', 'distance_from_airport_km',
            ], fn ($c) => Schema::hasColumn('islands', $c)));
        });
    }
};
