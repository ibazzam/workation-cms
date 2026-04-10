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

        Schema::table('blog_posts', function (Blueprint $table): void {
            if (!Schema::hasColumn('blog_posts', 'blog_category_slug')) {
                $table->string('blog_category_slug', 80)->nullable()->after('cover_image_path');
                $table->index('blog_category_slug', 'blog_posts_category_lookup');
            }

            if (!Schema::hasColumn('blog_posts', 'blog_tag_slugs')) {
                $table->json('blog_tag_slugs')->nullable()->after('blog_category_slug');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('blog_posts')) {
            return;
        }

        Schema::table('blog_posts', function (Blueprint $table): void {
            if (Schema::hasColumn('blog_posts', 'blog_tag_slugs')) {
                $table->dropColumn('blog_tag_slugs');
            }

            if (Schema::hasColumn('blog_posts', 'blog_category_slug')) {
                $table->dropIndex('blog_posts_category_lookup');
                $table->dropColumn('blog_category_slug');
            }
        });
    }
};
