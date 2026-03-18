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
        if (Schema::hasTable('portal_admin_action_requests')) {
            return;
        }

        Schema::create('portal_admin_action_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('action_type', 64);
            $table->foreignId('requested_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('requested_by_role', 32)->nullable();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('target_registration_id')->nullable();
            $table->string('target_identifier', 190)->nullable();
            $table->text('reason')->nullable();
            $table->longText('payload')->nullable();
            $table->string('status', 24)->default('pending');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['status', 'action_type', 'created_at']);
            $table->index(['target_user_id', 'action_type', 'status']);
            $table->index(['target_registration_id', 'action_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_admin_action_requests');
    }
};
