<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_water_sports_rental_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('vendor_property_id');
            $table->unsignedBigInteger('vendor_user_id');
            $table->string('name', 160);
            $table->string('equipment_type', 80)->default('other');
            $table->text('description')->nullable();
            $table->decimal('price_per_hour_local', 12, 2)->default(0);
            $table->decimal('price_per_hour_usd', 12, 2)->default(0);
            $table->decimal('price_per_hour_child_local', 12, 2)->default(0);
            $table->decimal('price_per_hour_child_usd', 12, 2)->default(0);
            $table->unsignedSmallInteger('min_age_years')->default(0);
            $table->string('equipment_category', 40)->default('non_motorized');
            $table->unsignedInteger('min_duration_minutes')->default(30);
            $table->unsignedInteger('max_duration_hours')->default(8);
            $table->unsignedInteger('quantity_available')->default(1);
            $table->string('status', 24)->default('active');
            $table->timestamps();
            $table->index(['vendor_property_id', 'status']);
            $table->index(['vendor_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_water_sports_rental_items');
    }
};
