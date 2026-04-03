<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conference_room_transfer_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conference_room_id')->constrained('conference_rooms')->onDelete('cascade');
            $table->enum('transfer_type', [
                'airport_pickup',
                'airport_dropoff',
                'airport_roundtrip',
                'inter_island',
                'speedboat',
                'resort_shuttle',
                'custom'
            ])->default('airport_pickup');
            $table->string('origin_location')->nullable(); // e.g., "Male Airport"
            $table->string('destination_location')->nullable(); // e.g., "Resort Conference Hall"
            $table->text('description')->nullable();
            $table->decimal('price_per_person', 10, 2);
            $table->integer('group_size_min')->default(1);
            $table->integer('group_size_max')->default(999);
            $table->integer('duration_minutes')->nullable(); // e.g., 45 minutes
            $table->string('availability')->default('daily'); // daily, weekdays, weekends, seasonal
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['conference_room_id']);
            $table->index(['transfer_type']);
            $table->index(['is_active']);
        });

        // Add resort fields to conference_rooms if not present
        if (!Schema::hasColumn('conference_rooms', 'is_resort_venue')) {
            Schema::table('conference_rooms', function (Blueprint $table) {
                $table->boolean('is_resort_venue')->default(false)->after('vendor_id');
                $table->string('resort_name')->nullable()->after('is_resort_venue');
                $table->string('airport_name')->nullable()->after('resort_name');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('conference_room_transfer_options');

        if (Schema::hasColumn('conference_rooms', 'is_resort_venue')) {
            Schema::table('conference_rooms', function (Blueprint $table) {
                $table->dropColumn(['is_resort_venue', 'resort_name', 'airport_name']);
            });
        }
    }
};
