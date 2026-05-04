<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds USD price columns for the foreign-visitor segment.
 *
 * Vendors now enter foreign rates in USD. The backend auto-computes the MVR
 * equivalent (USD × MVR_USD_RATE) and stores it in the existing *_price
 * columns so that booking logic and payment processing remain unchanged.
 *
 * Local rates continue to be stored in MVR via the *_price_local columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_property_room_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('vendor_property_room_categories', 'meal_plan_room_only_price_usd')) {
                $table->decimal('meal_plan_room_only_price_usd', 12, 2)->default(0)->nullable()->after('meal_plan_room_only_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'meal_plan_bb_price_usd')) {
                $table->decimal('meal_plan_bb_price_usd', 12, 2)->default(0)->nullable()->after('meal_plan_bb_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'meal_plan_hb_price_usd')) {
                $table->decimal('meal_plan_hb_price_usd', 12, 2)->default(0)->nullable()->after('meal_plan_hb_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'meal_plan_fb_price_usd')) {
                $table->decimal('meal_plan_fb_price_usd', 12, 2)->default(0)->nullable()->after('meal_plan_fb_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'meal_plan_ai_price_usd')) {
                $table->decimal('meal_plan_ai_price_usd', 12, 2)->default(0)->nullable()->after('meal_plan_ai_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'extra_person_price_usd')) {
                $table->decimal('extra_person_price_usd', 12, 2)->default(0)->nullable()->after('extra_person_price');
            }
            if (!Schema::hasColumn('vendor_property_room_categories', 'child_price_usd')) {
                $table->decimal('child_price_usd', 12, 2)->default(0)->nullable()->after('child_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vendor_property_room_categories', function (Blueprint $table) {
            foreach ([
                'meal_plan_room_only_price_usd',
                'meal_plan_bb_price_usd',
                'meal_plan_hb_price_usd',
                'meal_plan_fb_price_usd',
                'meal_plan_ai_price_usd',
                'extra_person_price_usd',
                'child_price_usd',
            ] as $column) {
                if (Schema::hasColumn('vendor_property_room_categories', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
