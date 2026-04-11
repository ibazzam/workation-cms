<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('blog_posts') && !Schema::hasColumn('blog_posts', 'article_images')) {
            Schema::table('blog_posts', function (Blueprint $table): void {
                $table->json('article_images')->nullable()->after('cover_image_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('blog_posts') && Schema::hasColumn('blog_posts', 'article_images')) {
            Schema::table('blog_posts', function (Blueprint $table): void {
                $table->dropColumn('article_images');
            });
        }
    }
};
