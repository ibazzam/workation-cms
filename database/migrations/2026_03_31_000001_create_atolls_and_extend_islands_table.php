<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('atolls')) {
            Schema::create('atolls', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('code', 20)->nullable();
                $table->string('wikipedia_title')->nullable();
                $table->string('source', 50)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('islands')) {
            return;
        }

        Schema::table('islands', function (Blueprint $table) {
            if (!Schema::hasColumn('islands', 'atoll_id')) {
                $table->foreignId('atoll_id')->nullable()->after('name');
            }

            if (!Schema::hasColumn('islands', 'is_inhabited')) {
                $table->boolean('is_inhabited')->nullable()->after('atoll_id');
            }

            if (!Schema::hasColumn('islands', 'wikipedia_title')) {
                $table->string('wikipedia_title')->nullable()->after('is_inhabited');
            }

            if (!Schema::hasColumn('islands', 'source')) {
                $table->string('source', 50)->nullable()->after('wikipedia_title');
            }
        });

        Schema::table('islands', function (Blueprint $table) {
            if (Schema::hasColumn('islands', 'atoll_id')) {
                try {
                    $table->foreign('atoll_id')->references('id')->on('atolls')->nullOnDelete();
                } catch (\Throwable $e) {
                    // Ignore if foreign key already exists in an existing environment.
                }

                try {
                    $table->index('atoll_id');
                } catch (\Throwable $e) {
                    // Ignore if index already exists.
                }
            }

            if (Schema::hasColumn('islands', 'is_inhabited')) {
                try {
                    $table->index('is_inhabited');
                } catch (\Throwable $e) {
                    // Ignore if index already exists.
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('islands')) {
            Schema::table('islands', function (Blueprint $table) {
                try {
                    $table->dropForeign(['atoll_id']);
                } catch (\Throwable $e) {
                    // Ignore if foreign key does not exist.
                }

                try {
                    $table->dropIndex(['atoll_id']);
                } catch (\Throwable $e) {
                    // Ignore if index does not exist.
                }

                try {
                    $table->dropIndex(['is_inhabited']);
                } catch (\Throwable $e) {
                    // Ignore if index does not exist.
                }

                if (Schema::hasColumn('islands', 'source')) {
                    $table->dropColumn('source');
                }

                if (Schema::hasColumn('islands', 'wikipedia_title')) {
                    $table->dropColumn('wikipedia_title');
                }

                if (Schema::hasColumn('islands', 'is_inhabited')) {
                    $table->dropColumn('is_inhabited');
                }

                if (Schema::hasColumn('islands', 'atoll_id')) {
                    $table->dropColumn('atoll_id');
                }
            });
        }

        Schema::dropIfExists('atolls');
    }
};
