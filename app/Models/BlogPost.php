<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_image_path',
        'article_images',
        'gallery_position',
        'blog_category_slug',
        'blog_tag_slugs',
        'is_featured',
        'is_published',
        'published_at',
        'created_by_user_id',
        'updated_by_user_id',
        'editorial_status',
        'editorial_notes',
        'reviewed_by_user_id',
        'reviewed_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'blog_tag_slugs' => 'array',
        'published_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'article_images' => 'array',
    ];
}
