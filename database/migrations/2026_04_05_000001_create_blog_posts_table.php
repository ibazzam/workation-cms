<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('blog_posts')) {
            Schema::create('blog_posts', function (Blueprint $table): void {
                $table->id();
                $table->string('title', 180);
                $table->string('slug', 220)->unique();
                $table->string('excerpt', 420)->nullable();
                $table->longText('content');
                $table->string('cover_image_path', 255)->nullable();
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_published')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->unsignedBigInteger('created_by_user_id')->nullable();
                $table->unsignedBigInteger('updated_by_user_id')->nullable();
                $table->timestamps();

                $table->index(['is_published', 'published_at'], 'blog_posts_publish_lookup');
                $table->index(['is_featured', 'published_at'], 'blog_posts_feature_lookup');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('blog_posts')) {
            Schema::dropIfExists('blog_posts');
        }
    }
};
