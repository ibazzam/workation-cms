<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conference_room_facilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conference_room_id')->constrained('conference_rooms')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable(); 
            $table->string('category'); // 'equipment', 'refreshment', 'catering', 'service', etc.
            $table->boolean('is_free')->default(false);
            $table->enum('pricing_type', ['hourly', 'per_unit', 'per_pax', 'per_meal', 'flat'])->default('flat');
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency')->default('MVR');
            $table->integer('quantity_available')->nullable(); // For per-unit pricing
            $table->boolean('is_available')->default(true);
            $table->timestamps();
            
            $table->index(['conference_room_id']);
            $table->index(['category']);
            $table->index(['is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conference_room_facilities');
    }
};
