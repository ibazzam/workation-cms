<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('finance_payout_batch_items')) {
            return;
        }

        Schema::table('finance_payout_batch_items', function (Blueprint $table): void {
            if (!Schema::hasColumn('finance_payout_batch_items', 'payout_account_id')) {
                $table->unsignedBigInteger('payout_account_id')->nullable()->after('vendor_user_id');
            }
            if (!Schema::hasColumn('finance_payout_batch_items', 'payout_account_currency')) {
                $table->string('payout_account_currency', 8)->nullable()->after('currency');
            }
            if (!Schema::hasColumn('finance_payout_batch_items', 'payout_account_verification_status')) {
                $table->string('payout_account_verification_status', 24)->nullable()->after('payout_account_currency');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('finance_payout_batch_items')) {
            return;
        }

        Schema::table('finance_payout_batch_items', function (Blueprint $table): void {
            foreach (['payout_account_verification_status', 'payout_account_currency', 'payout_account_id'] as $column) {
                if (Schema::hasColumn('finance_payout_batch_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
