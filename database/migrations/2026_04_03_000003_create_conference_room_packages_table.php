<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conference_room_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conference_room_id')->constrained('conference_rooms')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_days')->default(1);
            $table->enum('package_type', ['basic', 'standard', 'premium', 'custom'])->default('standard');
            $table->decimal('base_price', 10, 2);
            $table->string('currency')->default('MVR');
            $table->decimal('discount_percentage', 5, 2)->default(0); // e.g., 10 for 10% off
            $table->boolean('is_active')->default(true);
            $table->text('vendor_notes')->nullable();
            $table->timestamps();
            
            $table->index(['conference_room_id']);
            $table->index(['package_type']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conference_room_packages');
    }
};
