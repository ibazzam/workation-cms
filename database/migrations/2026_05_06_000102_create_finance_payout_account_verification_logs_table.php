<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_payout_account_verification_logs')) {
            return;
        }

        Schema::create('finance_payout_account_verification_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('payout_account_id')->index();
            $table->unsignedBigInteger('vendor_user_id')->index();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->boolean('crosscheck_business_profile')->default(false);
            $table->boolean('crosscheck_service_profile')->default(false);
            $table->boolean('crosscheck_id_proof')->default(false);
            $table->boolean('sole_proprietor_personal_name_allowed')->default(false);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->timestamps();

            $table->index(['payout_account_id', 'created_at']);
            $table->index(['vendor_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payout_account_verification_logs');
    }
};
