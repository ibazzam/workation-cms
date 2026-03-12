<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('transport_provider_jobs')) {
            Schema::create('transport_provider_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('provider')->nullable();
                $table->string('action');
                $table->json('payload')->nullable();
                $table->string('status')->default('pending');
                $table->integer('attempts')->default(0);
                $table->timestampTz('next_attempt_at')->nullable();
                $table->text('last_error')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_provider_jobs');
    }
};
