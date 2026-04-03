<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_amenities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained('vendor_properties')->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->enum('amenity_category', [
                'connectivity', 'dining', 'wellness', 'transport', 'services', 'recreation', 'parking'
            ])->default('services');
            $table->enum('pricing_type', [
                'nightly', 'per_pax', 'per_pax_per_night', 'one_time', 'flat'
            ])->default('one_time');
            $table->decimal('price', 12, 2)->default(0);
            $table->string('currency', 8)->default('MVR');
            $table->boolean('is_included_in_base')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['property_id', 'amenity_category']);
            $table->index(['property_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_amenities');
    }
};
