<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('transport_provider_jobs')) {
            Schema::table('transport_provider_jobs', function (Blueprint $table) {
                $table->string('request_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('transport_provider_jobs')) {
            Schema::table('transport_provider_jobs', function (Blueprint $table) {
                if (Schema::hasColumn('transport_provider_jobs', 'request_id')) {
                    $table->dropIndex(['request_id']);
                    $table->dropColumn('request_id');
                }
            });
        }
    }
};
