<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_water_sports_rental_items', function (Blueprint $table): void {
            // Pricing model: hourly rental vs fixed per-seat/per-person
            $table->string('pricing_type', 16)->default('hourly')->after('equipment_category');

            // Per-seat pricing (parasailing, banana boat, etc.)
            $table->decimal('price_per_seat_adult_local', 12, 2)->default(0)->after('pricing_type');
            $table->decimal('price_per_seat_adult_usd', 12, 2)->default(0)->after('price_per_seat_adult_local');
            $table->decimal('price_per_seat_child_local', 12, 2)->default(0)->after('price_per_seat_adult_usd');
            $table->decimal('price_per_seat_child_usd', 12, 2)->default(0)->after('price_per_seat_child_local');

            // Safety requirements
            $table->boolean('requires_swimming')->default(false)->after('min_age_years');
            $table->text('safety_notes')->nullable()->after('requires_swimming');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_water_sports_rental_items', function (Blueprint $table): void {
            $table->dropColumn([
                'pricing_type',
                'price_per_seat_adult_local',
                'price_per_seat_adult_usd',
                'price_per_seat_child_local',
                'price_per_seat_child_usd',
                'requires_swimming',
                'safety_notes',
            ]);
        });
    }
};
