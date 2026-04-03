<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_amenity', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('package_id')->constrained('accommodation_packages')->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained('room_amenities')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['package_id', 'amenity_id']);
            $table->index(['package_id']);
            $table->index(['amenity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_amenity');
    }
};
