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
        if (Schema::hasTable('portal_admin_audit_logs')) {
            return;
        }

        Schema::create('portal_admin_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name', 120)->nullable();
            $table->string('actor_role', 32)->nullable();
            $table->string('action', 80);
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('target_identifier', 190)->nullable();
            $table->string('target_role', 32)->nullable();
            $table->longText('details')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index(['actor_role', 'created_at']);
            $table->index(['target_role', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portal_admin_audit_logs');
    }
};
