<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_property_room_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_property_room_categories', 'meal_plan_room_only_price_local')) {
                $table->decimal('meal_plan_room_only_price_local', 12, 2)->default(0)->after('meal_plan_room_only_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'meal_plan_bb_price_local')) {
                $table->decimal('meal_plan_bb_price_local', 12, 2)->default(0)->after('meal_plan_bb_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'meal_plan_hb_price_local')) {
                $table->decimal('meal_plan_hb_price_local', 12, 2)->default(0)->after('meal_plan_hb_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'meal_plan_fb_price_local')) {
                $table->decimal('meal_plan_fb_price_local', 12, 2)->default(0)->after('meal_plan_fb_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'meal_plan_ai_price_local')) {
                $table->decimal('meal_plan_ai_price_local', 12, 2)->default(0)->after('meal_plan_ai_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'extra_person_price_local')) {
                $table->decimal('extra_person_price_local', 12, 2)->default(0)->after('extra_person_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'child_price_local')) {
                $table->decimal('child_price_local', 12, 2)->default(0)->after('child_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_property_room_categories', function (Blueprint $table) {
            foreach ([
                'meal_plan_room_only_price_local',
                'meal_plan_bb_price_local',
                'meal_plan_hb_price_local',
                'meal_plan_fb_price_local',
                'meal_plan_ai_price_local',
                'extra_person_price_local',
                'child_price_local',
            ] as $column) {
                if (Schema::hasColumn('vendor_property_room_categories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
