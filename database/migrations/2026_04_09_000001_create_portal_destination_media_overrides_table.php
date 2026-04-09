<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('portal_destination_media_overrides')) {
            return;
        }

        Schema::create('portal_destination_media_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('destination_key', 190)->unique();
            $table->string('destination_name', 190);
            $table->string('destination_type', 40)->nullable();
            $table->string('image_value', 2048);
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();

            $table->index(['destination_type', 'destination_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_destination_media_overrides');
    }
};