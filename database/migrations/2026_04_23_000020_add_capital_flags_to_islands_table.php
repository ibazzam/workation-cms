<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('islands')) {
            return;
        }

        Schema::table('islands', function (Blueprint $table) {
            if (!Schema::hasColumn('islands', 'is_country_capital')) {
                $table->boolean('is_country_capital')->default(false)->after('is_inhabited');
                $table->index('is_country_capital', 'islands_is_country_capital_idx');
            }

            if (!Schema::hasColumn('islands', 'is_atoll_capital')) {
                $table->boolean('is_atoll_capital')->default(false)->after('is_country_capital');
                $table->index(['atoll_id', 'is_atoll_capital'], 'islands_atoll_capital_lookup_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('islands')) {
            return;
        }

        Schema::table('islands', function (Blueprint $table) {
            if (Schema::hasColumn('islands', 'is_atoll_capital')) {
                $table->dropIndex('islands_atoll_capital_lookup_idx');
                $table->dropColumn('is_atoll_capital');
            }

            if (Schema::hasColumn('islands', 'is_country_capital')) {
                $table->dropIndex('islands_is_country_capital_idx');
                $table->dropColumn('is_country_capital');
            }
        });
    }
};
