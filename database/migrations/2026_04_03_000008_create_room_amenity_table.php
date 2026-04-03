<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_amenity', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')->constrained('accommodation_rooms')->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained('room_amenities')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['room_id', 'amenity_id']);
            $table->index(['room_id']);
            $table->index(['amenity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_amenity');
    }
};
