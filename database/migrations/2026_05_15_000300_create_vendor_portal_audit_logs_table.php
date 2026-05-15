<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_portal_audit_logs')) {
            Schema::create('vendor_portal_audit_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vendor_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('actor_name', 160)->nullable();
                $table->string('actor_email', 190)->nullable();
                $table->string('action', 120);
                $table->string('severity', 20)->default('info');
                $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('target_identifier', 190)->nullable();
                $table->json('details')->nullable();
                $table->timestamps();

                $table->index(['vendor_user_id', 'created_at'], 'vendor_portal_audit_logs_vendor_created_idx');
                $table->index(['severity', 'created_at'], 'vendor_portal_audit_logs_severity_created_idx');
                $table->index(['action', 'created_at'], 'vendor_portal_audit_logs_action_created_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_portal_audit_logs');
    }
};
