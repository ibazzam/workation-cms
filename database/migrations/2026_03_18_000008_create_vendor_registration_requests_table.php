<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('vendor_registration_requests')) {
            return;
        }

        Schema::create('vendor_registration_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('business_name', 160);
            $table->string('contact_name', 120);
            $table->string('email', 160);
            $table->string('phone', 40)->nullable();
            $table->string('business_registration_number', 80);
            $table->string('license_number', 80);
            $table->string('business_license_document_path', 255);
            $table->string('verification_document_path', 255)->nullable();
            $table->string('status', 24)->default('pending');
            $table->longText('review_notes')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('approved_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['email', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_registration_requests');
    }
};
