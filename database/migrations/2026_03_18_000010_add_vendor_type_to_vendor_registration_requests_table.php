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
        if (!Schema::hasTable('vendor_registration_requests')) {
            return;
        }

        if (Schema::hasColumn('vendor_registration_requests', 'vendor_type')) {
            return;
        }

        Schema::table('vendor_registration_requests', function (Blueprint $table): void {
            $table->string('vendor_type', 40)->default('other')->after('phone');
            $table->index(['vendor_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('vendor_registration_requests')) {
            return;
        }

        if (!Schema::hasColumn('vendor_registration_requests', 'vendor_type')) {
            return;
        }

        Schema::table('vendor_registration_requests', function (Blueprint $table): void {
            $table->dropIndex('vendor_registration_requests_vendor_type_status_index');
            $table->dropColumn('vendor_type');
        });
    }
};
