<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conference_rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('atoll')->nullable();
            $table->string('island')->nullable();
            $table->integer('capacity')->default(0);
            $table->decimal('base_price', 10, 2)->default(0);
            $table->string('currency')->default('MVR');
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->json('amenities')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('vendor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['atoll', 'island']);
            $table->index(['is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conference_rooms');
    }
};
