<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_payout_accounts')) {
            return;
        }

        Schema::table('vendor_payout_accounts', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_payout_accounts', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('is_primary');
            }
            if (!Schema::hasColumn('vendor_payout_accounts', 'verification_status')) {
                $table->string('verification_status', 24)->default('verified')->after('is_active');
            }
            if (!Schema::hasColumn('vendor_payout_accounts', 'verification_notes')) {
                $table->text('verification_notes')->nullable()->after('verification_status');
            }
            if (!Schema::hasColumn('vendor_payout_accounts', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verification_notes');
            }
            if (!Schema::hasColumn('vendor_payout_accounts', 'verified_by_user_id')) {
                $table->unsignedBigInteger('verified_by_user_id')->nullable()->after('verified_at');
            }
        });

        DB::table('vendor_payout_accounts')
            ->whereNull('verification_status')
            ->update([
                'verification_status' => 'verified',
                'verification_notes' => DB::raw("COALESCE(verification_notes, 'Legacy account migrated as verified')"),
                'verified_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_payout_accounts')) {
            return;
        }

        Schema::table('vendor_payout_accounts', function (Blueprint $table): void {
            foreach (['verified_by_user_id', 'verified_at', 'verification_notes', 'verification_status', 'is_active'] as $column) {
                if (Schema::hasColumn('vendor_payout_accounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
