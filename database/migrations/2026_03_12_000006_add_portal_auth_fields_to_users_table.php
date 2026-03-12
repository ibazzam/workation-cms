<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('name');
            }

            if (!Schema::hasColumn('users', 'portal_role')) {
                $table->string('portal_role', 32)->nullable()->after('password');
            }

            if (!Schema::hasColumn('users', 'portal_enabled')) {
                $table->boolean('portal_enabled')->default(false)->after('portal_role');
            }

            if (!Schema::hasColumn('users', 'portal_vendor_id')) {
                $table->string('portal_vendor_id')->nullable()->after('portal_enabled');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            // Add indexes in a separate step for compatibility across engines.
            $table->index('portal_role');
            $table->index('portal_enabled');
            $table->index('portal_vendor_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['portal_role']);
            $table->dropIndex(['portal_enabled']);
            $table->dropIndex(['portal_vendor_id']);

            if (Schema::hasColumn('users', 'portal_vendor_id')) {
                $table->dropColumn('portal_vendor_id');
            }

            if (Schema::hasColumn('users', 'portal_enabled')) {
                $table->dropColumn('portal_enabled');
            }

            if (Schema::hasColumn('users', 'portal_role')) {
                $table->dropColumn('portal_role');
            }

            if (Schema::hasColumn('users', 'username')) {
                $table->dropUnique('users_username_unique');
                $table->dropColumn('username');
            }
        });
    }
};
