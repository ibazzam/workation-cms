<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('portal_finance_settings')) {
            Schema::create('portal_finance_settings', function (Blueprint $table): void {
                $table->id();
                $table->string('setting_key', 80)->unique();
                $table->decimal('value_decimal', 12, 4)->nullable();
                $table->string('value_string', 190)->nullable();
                $table->longText('value_json')->nullable();
                $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['setting_key', 'updated_at']);
            });
        }

        if (!Schema::hasTable('portal_finance_adjustments')) {
            Schema::create('portal_finance_adjustments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('vendor_user_id')->constrained('users')->cascadeOnDelete();
                $table->date('applies_on');
                $table->string('adjustment_type', 40);
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('currency', 8)->default('MVR');
                $table->string('invoice_reference', 64)->nullable();
                $table->text('reason');
                $table->string('status', 24)->default('approved');
                $table->foreignId('moderated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('moderated_by_role', 32)->nullable();
                $table->timestamps();

                $table->index(['applies_on', 'vendor_user_id']);
                $table->index(['status', 'created_at']);
                $table->index(['adjustment_type', 'applies_on']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_finance_adjustments');
        Schema::dropIfExists('portal_finance_settings');
    }
};
