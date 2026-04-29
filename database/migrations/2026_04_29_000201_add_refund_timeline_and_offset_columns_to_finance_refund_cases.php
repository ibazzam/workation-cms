<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('finance_refund_cases')) {
            return;
        }

        Schema::table('finance_refund_cases', function (Blueprint $table): void {
            if (!Schema::hasColumn('finance_refund_cases', 'review_started_at')) {
                $table->timestamp('review_started_at')->nullable()->after('reviewed_at');
            }
            if (!Schema::hasColumn('finance_refund_cases', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('review_started_at');
            }
            if (!Schema::hasColumn('finance_refund_cases', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('finance_refund_cases', 'offset_mode')) {
                $table->string('offset_mode', 40)->nullable()->after('resolution_notes');
            }
            if (!Schema::hasColumn('finance_refund_cases', 'offset_amount')) {
                $table->decimal('offset_amount', 14, 4)->nullable()->after('offset_mode');
            }
            if (!Schema::hasColumn('finance_refund_cases', 'offset_applied_at')) {
                $table->timestamp('offset_applied_at')->nullable()->after('offset_amount');
            }
            if (!Schema::hasColumn('finance_refund_cases', 'sla_due_at')) {
                $table->timestamp('sla_due_at')->nullable()->after('offset_applied_at');
            }
            if (!Schema::hasColumn('finance_refund_cases', 'sla_escalated_at')) {
                $table->timestamp('sla_escalated_at')->nullable()->after('sla_due_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('finance_refund_cases')) {
            return;
        }

        Schema::table('finance_refund_cases', function (Blueprint $table): void {
            foreach ([
                'sla_escalated_at',
                'sla_due_at',
                'offset_applied_at',
                'offset_amount',
                'offset_mode',
                'rejected_at',
                'approved_at',
                'review_started_at',
            ] as $column) {
                if (Schema::hasColumn('finance_refund_cases', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
