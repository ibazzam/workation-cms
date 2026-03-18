<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'google_oauth_id')) {
                $table->string('google_oauth_id', 191)->nullable()->after('portal_vendor_id');
                $table->index('google_oauth_id');
            }

            if (!Schema::hasColumn('users', 'facebook_oauth_id')) {
                $table->string('facebook_oauth_id', 191)->nullable()->after('google_oauth_id');
                $table->index('facebook_oauth_id');
            }

            if (!Schema::hasColumn('users', 'apple_oauth_id')) {
                $table->string('apple_oauth_id', 191)->nullable()->after('facebook_oauth_id');
                $table->index('apple_oauth_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'google_oauth_id')) {
                $table->dropIndex(['google_oauth_id']);
                $table->dropColumn('google_oauth_id');
            }

            if (Schema::hasColumn('users', 'facebook_oauth_id')) {
                $table->dropIndex(['facebook_oauth_id']);
                $table->dropColumn('facebook_oauth_id');
            }

            if (Schema::hasColumn('users', 'apple_oauth_id')) {
                $table->dropIndex(['apple_oauth_id']);
                $table->dropColumn('apple_oauth_id');
            }
        });
    }
};
