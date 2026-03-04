<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_operators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider_type')->nullable();
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('transport_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_island_id')->constrained('islands')->onDelete('cascade');
            $table->foreignId('destination_island_id')->constrained('islands')->onDelete('cascade');
            $table->integer('distance_km')->nullable();
            $table->json('route_meta')->nullable();
            $table->timestamps();
        });

        Schema::create('transport_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained('transport_routes')->onDelete('cascade');
            $table->foreignId('operator_id')->constrained('transport_operators')->onDelete('cascade');
            $table->timestampTz('departure_at');
            $table->timestampTz('arrival_at')->nullable();
            $table->string('published_version')->nullable();
            $table->timestamps();
        });

        Schema::create('transport_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('transport_schedules')->onDelete('cascade');
            $table->string('seat_class')->nullable();
            $table->integer('total_seats')->default(0);
            $table->integer('reserved_seats')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('transport_holds', function (Blueprint $table) {
            $table->id();
            $table->string('quote_id')->nullable();
            $table->foreignId('schedule_id')->constrained('transport_schedules')->onDelete('cascade');
            $table->string('seat_class')->nullable();
            $table->integer('seats_reserved')->default(0);
            $table->string('status')->default('held');
            $table->timestampTz('ttl_expires_at')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->json('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_holds');
        Schema::dropIfExists('transport_inventory');
        Schema::dropIfExists('transport_schedules');
        Schema::dropIfExists('transport_routes');
        Schema::dropIfExists('transport_operators');
    }
};
