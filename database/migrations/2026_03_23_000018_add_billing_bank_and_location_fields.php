<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vendor_billing_details')) {
            return;
        }

        Schema::table('vendor_billing_details', function (Blueprint $table): void {
            if (!Schema::hasColumn('vendor_billing_details', 'beneficiary_name')) {
                $table->string('beneficiary_name', 190)->nullable()->after('payout_method');
            }
            if (!Schema::hasColumn('vendor_billing_details', 'bank_account_number')) {
                $table->string('bank_account_number', 60)->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('vendor_billing_details', 'swift_code')) {
                $table->string('swift_code', 20)->nullable()->after('bank_account_number');
            }
            if (!Schema::hasColumn('vendor_billing_details', 'billing_street_name')) {
                $table->string('billing_street_name', 255)->nullable()->after('billing_address');
            }
            if (!Schema::hasColumn('vendor_billing_details', 'billing_country')) {
                $table->string('billing_country', 90)->nullable()->after('billing_street_name');
            }
            if (!Schema::hasColumn('vendor_billing_details', 'billing_state')) {
                $table->string('billing_state', 120)->nullable()->after('billing_country');
            }
            if (!Schema::hasColumn('vendor_billing_details', 'billing_city')) {
                $table->string('billing_city', 120)->nullable()->after('billing_state');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('vendor_billing_details')) {
            return;
        }

        Schema::table('vendor_billing_details', function (Blueprint $table): void {
            if (Schema::hasColumn('vendor_billing_details', 'billing_city')) {
                $table->dropColumn('billing_city');
            }
            if (Schema::hasColumn('vendor_billing_details', 'billing_state')) {
                $table->dropColumn('billing_state');
            }
            if (Schema::hasColumn('vendor_billing_details', 'billing_country')) {
                $table->dropColumn('billing_country');
            }
            if (Schema::hasColumn('vendor_billing_details', 'billing_street_name')) {
                $table->dropColumn('billing_street_name');
            }
            if (Schema::hasColumn('vendor_billing_details', 'swift_code')) {
                $table->dropColumn('swift_code');
            }
            if (Schema::hasColumn('vendor_billing_details', 'bank_account_number')) {
                $table->dropColumn('bank_account_number');
            }
            if (Schema::hasColumn('vendor_billing_details', 'beneficiary_name')) {
                $table->dropColumn('beneficiary_name');
            }
        });
    }
};
