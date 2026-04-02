<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('vendor_property_room_categories')) {
            return;
        }

        Schema::table('vendor_property_room_categories', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_property_room_categories', 'room_size_sqm')) {
                $table->unsignedInteger('room_size_sqm')->nullable()->after('max_occupancy');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'floor_info')) {
                $table->string('floor_info', 80)->nullable()->after('room_size_sqm');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'has_window')) {
                $table->boolean('has_window')->default(true)->after('floor_info');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'non_smoking')) {
                $table->boolean('non_smoking')->default(true)->after('has_window');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'child_policy')) {
                $table->text('child_policy')->nullable()->after('bathroom_amenities');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'extra_bed_policy')) {
                $table->text('extra_bed_policy')->nullable()->after('child_policy');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_property_room_categories')) {
            return;
        }

        Schema::table('vendor_property_room_categories', function (Blueprint $table): void {
            $columns = [
                'room_size_sqm',
                'floor_info',
                'has_window',
                'non_smoking',
                'child_policy',
                'extra_bed_policy',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('vendor_property_room_categories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
