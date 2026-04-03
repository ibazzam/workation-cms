<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accommodation_packages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained('vendor_properties')->cascadeOnDelete();
            $table->foreignId('room_id')->constrained('accommodation_rooms')->cascadeOnDelete();
            $table->string('package_name', 160);
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_nights')->default(3);
            $table->decimal('base_price', 12, 2)->default(0);
            $table->unsignedInteger('discount_percentage')->default(0);
            $table->string('currency', 8)->default('MVR');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['property_id', 'room_id']);
            $table->index(['property_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodation_packages');
    }
};
