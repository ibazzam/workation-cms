<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finance_payout_item_status_logs')) {
            return;
        }

        Schema::create('finance_payout_item_status_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('batch_id')->index();
            $table->unsignedBigInteger('item_id')->index();
            $table->unsignedBigInteger('vendor_user_id')->nullable()->index();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->string('bank_reference', 160)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->timestamps();

            $table->index(['batch_id', 'created_at']);
            $table->index(['item_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_payout_item_status_logs');
    }
};
