<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('User')) {
            return;
        }

        if (!Schema::hasColumn('User', 'id')) {
            return;
        }

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Ensure UUID generator is available in Postgres.
        DB::statement('CREATE EXTENSION IF NOT EXISTS "pgcrypto"');

        // Set a DB-level fallback so inserts without id do not fail.
        DB::statement('ALTER TABLE "User" ALTER COLUMN "id" SET DEFAULT gen_random_uuid()');
    }

    public function down(): void
    {
        if (!Schema::hasTable('User')) {
            return;
        }

        if (!Schema::hasColumn('User', 'id')) {
            return;
        }

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE "User" ALTER COLUMN "id" DROP DEFAULT');
    }
};
