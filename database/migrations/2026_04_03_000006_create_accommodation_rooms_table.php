<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accommodation_rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained('vendor_properties')->cascadeOnDelete();
            $table->enum('room_type', ['single', 'double', 'triple', 'suite', 'villa'])->default('double');
            $table->unsignedInteger('capacity_guests')->default(2);
            $table->unsignedInteger('total_rooms_available')->default(1);
            $table->decimal('base_price_per_night', 12, 2)->default(0);
            $table->string('currency', 8)->default('MVR');
            $table->text('description')->nullable();
            $table->unsignedInteger('max_occupancy')->default(2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['property_id', 'room_type']);
            $table->index(['property_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodation_rooms');
    }
};
