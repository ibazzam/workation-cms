<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('finance_payout_batches')) {
            return;
        }

        Schema::table('finance_payout_batches', function (Blueprint $table): void {
            if (!Schema::hasColumn('finance_payout_batches', 'settlement_reference_id')) {
                $table->string('settlement_reference_id', 160)->nullable()->after('bank_reference');
            }
            if (!Schema::hasColumn('finance_payout_batches', 'settlement_reference_proof')) {
                $table->text('settlement_reference_proof')->nullable()->after('settlement_reference_id');
            }
            if (!Schema::hasColumn('finance_payout_batches', 'settlement_verified_at')) {
                $table->timestamp('settlement_verified_at')->nullable()->after('confirmed_at');
            }
            if (!Schema::hasColumn('finance_payout_batches', 'settlement_verified_by_user_id')) {
                $table->unsignedBigInteger('settlement_verified_by_user_id')->nullable()->after('settlement_verified_at');
            }
            if (!Schema::hasColumn('finance_payout_batches', 'first_approved_at')) {
                $table->timestamp('first_approved_at')->nullable()->after('settlement_verified_by_user_id');
            }
            if (!Schema::hasColumn('finance_payout_batches', 'first_approved_by_user_id')) {
                $table->unsignedBigInteger('first_approved_by_user_id')->nullable()->after('first_approved_at');
            }
            if (!Schema::hasColumn('finance_payout_batches', 'second_approved_at')) {
                $table->timestamp('second_approved_at')->nullable()->after('first_approved_by_user_id');
            }
            if (!Schema::hasColumn('finance_payout_batches', 'second_approved_by_user_id')) {
                $table->unsignedBigInteger('second_approved_by_user_id')->nullable()->after('second_approved_at');
            }
            if (!Schema::hasColumn('finance_payout_batches', 'ready_notified_at')) {
                $table->timestamp('ready_notified_at')->nullable()->after('second_approved_by_user_id');
            }

            $table->index(['status', 'first_approved_by_user_id', 'second_approved_by_user_id'], 'fpb_status_four_eyes_idx');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('finance_payout_batches')) {
            return;
        }

        Schema::table('finance_payout_batches', function (Blueprint $table): void {
            if (Schema::hasColumn('finance_payout_batches', 'ready_notified_at')) {
                $table->dropColumn('ready_notified_at');
            }
            if (Schema::hasColumn('finance_payout_batches', 'second_approved_by_user_id')) {
                $table->dropColumn('second_approved_by_user_id');
            }
            if (Schema::hasColumn('finance_payout_batches', 'second_approved_at')) {
                $table->dropColumn('second_approved_at');
            }
            if (Schema::hasColumn('finance_payout_batches', 'first_approved_by_user_id')) {
                $table->dropColumn('first_approved_by_user_id');
            }
            if (Schema::hasColumn('finance_payout_batches', 'first_approved_at')) {
                $table->dropColumn('first_approved_at');
            }
            if (Schema::hasColumn('finance_payout_batches', 'settlement_verified_by_user_id')) {
                $table->dropColumn('settlement_verified_by_user_id');
            }
            if (Schema::hasColumn('finance_payout_batches', 'settlement_verified_at')) {
                $table->dropColumn('settlement_verified_at');
            }
            if (Schema::hasColumn('finance_payout_batches', 'settlement_reference_proof')) {
                $table->dropColumn('settlement_reference_proof');
            }
            if (Schema::hasColumn('finance_payout_batches', 'settlement_reference_id')) {
                $table->dropColumn('settlement_reference_id');
            }

            $table->dropIndex('fpb_status_four_eyes_idx');
        });
    }
};
