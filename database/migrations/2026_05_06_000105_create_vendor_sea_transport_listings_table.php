<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_sea_transport_listings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('vendor_property_id')->index();
            $table->unsignedBigInteger('vendor_user_id')->index();
            $table->string('name', 200)->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->string('listing_moderation_status', 32)->default('pending')->index();
            $table->string('listing_category', 64)->default('sea_transport')->index();
            $table->string('listing_admin_notes', 500)->nullable();
            $table->timestamp('listing_submitted_for_review_at')->nullable();
            $table->timestamp('listing_approved_at')->nullable();
            $table->unsignedBigInteger('listing_approved_by_user_id')->nullable();
            $table->string('location', 300)->nullable();
            $table->string('atoll', 120)->nullable()->index();
            $table->string('island', 120)->nullable()->index();
            $table->string('city', 120)->nullable()->index();
            $table->string('location_country', 80)->nullable()->default('Maldives');
            $table->text('description')->nullable();
            $table->decimal('base_price', 12, 2)->nullable()->default(0);
            $table->string('currency', 10)->nullable()->default('MVR');
            $table->integer('max_guests')->nullable()->default(0);
            $table->json('listing_details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_sea_transport_listings');
    }
};
