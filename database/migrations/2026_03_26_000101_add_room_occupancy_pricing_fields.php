<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_property_room_categories')) {
            return;
        }

        Schema::table('vendor_property_room_categories', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_property_room_categories', 'extra_person_capacity')) {
                $table->unsignedInteger('extra_person_capacity')->default(0)->after('max_occupancy');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'child_capacity')) {
                $table->unsignedInteger('child_capacity')->default(0)->after('extra_person_capacity');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'extra_person_price')) {
                $table->decimal('extra_person_price', 12, 2)->default(0)->after('base_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'child_price')) {
                $table->decimal('child_price', 12, 2)->default(0)->after('extra_person_price');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_property_room_categories')) {
            return;
        }

        Schema::table('vendor_property_room_categories', function (Blueprint $table): void {
            if (Schema::hasColumn('vendor_property_room_categories', 'child_price')) {
                $table->dropColumn('child_price');
            }
            if (Schema::hasColumn('vendor_property_room_categories', 'extra_person_price')) {
                $table->dropColumn('extra_person_price');
            }
            if (Schema::hasColumn('vendor_property_room_categories', 'child_capacity')) {
                $table->dropColumn('child_capacity');
            }
            if (Schema::hasColumn('vendor_property_room_categories', 'extra_person_capacity')) {
                $table->dropColumn('extra_person_capacity');
            }
        });
    }
};
