<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('blog_posts')) {
            return;
        }

        Schema::table('blog_posts', function (Blueprint $table) {
            if (!Schema::hasColumn('blog_posts', 'editorial_status')) {
                // draft | pending_review | approved | rejected
                $table->string('editorial_status', 32)->default('draft')->after('is_published');
            }
            if (!Schema::hasColumn('blog_posts', 'editorial_notes')) {
                $table->text('editorial_notes')->nullable()->after('editorial_status');
            }
            if (!Schema::hasColumn('blog_posts', 'reviewed_by_user_id')) {
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable()->after('editorial_notes');
            }
            if (!Schema::hasColumn('blog_posts', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by_user_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('blog_posts')) {
            return;
        }

        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('blog_posts', 'editorial_status') ? 'editorial_status' : null,
                Schema::hasColumn('blog_posts', 'editorial_notes') ? 'editorial_notes' : null,
                Schema::hasColumn('blog_posts', 'reviewed_by_user_id') ? 'reviewed_by_user_id' : null,
                Schema::hasColumn('blog_posts', 'reviewed_at') ? 'reviewed_at' : null,
            ]));
        });
    }
};
