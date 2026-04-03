<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_facility', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('conference_room_packages')->onDelete('cascade');
            $table->foreignId('facility_id')->constrained('conference_room_facilities')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['package_id', 'facility_id']);
            $table->index(['package_id']);
            $table->index(['facility_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_facility');
    }
};
