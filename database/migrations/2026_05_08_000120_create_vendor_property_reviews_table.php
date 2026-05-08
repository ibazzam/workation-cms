<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vendor_property_reviews')) {
            return;
        }

        Schema::create('vendor_property_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vendor_property_id')->index();
            $table->unsignedBigInteger('reservation_id')->unique(); // one review per booking
            $table->unsignedBigInteger('customer_user_id')->nullable()->index();
            $table->string('customer_name', 120)->default('');
            $table->tinyInteger('rating')->unsigned()->default(5); // 1–5
            $table->text('review_comment');
            $table->string('status', 32)->default('approved')->index(); // approved|pending|rejected
            $table->timestamps();

            $table->index(['vendor_property_id', 'status'], 'vpr_property_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_property_reviews');
    }
};
