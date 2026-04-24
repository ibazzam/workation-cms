<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_property_room_categories')) {
            return;
        }

        Schema::table('vendor_property_room_categories', static function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_property_room_categories', 'meal_plan_room_only_price')) {
                $table->decimal('meal_plan_room_only_price', 12, 2)->default(0)->after('base_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'meal_plan_bb_price')) {
                $table->decimal('meal_plan_bb_price', 12, 2)->default(0)->after('meal_plan_room_only_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'meal_plan_hb_price')) {
                $table->decimal('meal_plan_hb_price', 12, 2)->default(0)->after('meal_plan_bb_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'meal_plan_fb_price')) {
                $table->decimal('meal_plan_fb_price', 12, 2)->default(0)->after('meal_plan_hb_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'meal_plan_ai_price')) {
                $table->decimal('meal_plan_ai_price', 12, 2)->default(0)->after('meal_plan_fb_price');
            }
        });

        // Backfill BB from legacy breakfast column if present.
        if (Schema::hasColumn('vendor_property_room_categories', 'meal_plan_breakfast_price')) {
            DB::table('vendor_property_room_categories')
                ->where('meal_plan_bb_price', '<=', 0)
                ->where('meal_plan_breakfast_price', '>', 0)
                ->update(['meal_plan_bb_price' => DB::raw('meal_plan_breakfast_price')]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_property_room_categories')) {
            return;
        }

        Schema::table('vendor_property_room_categories', static function (Blueprint $table): void {
            foreach (['meal_plan_ai_price', 'meal_plan_fb_price', 'meal_plan_hb_price', 'meal_plan_bb_price', 'meal_plan_room_only_price'] as $column) {
                if (Schema::hasColumn('vendor_property_room_categories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
