<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBlogFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_render_and_delete_blog_post_with_cover_image(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'username' => 'content_admin',
            'portal_role' => 'ADMIN_SUPER',
            'portal_enabled' => true,
        ]);

        $content = "## Section Heading\n\nThis paragraph contains **bold** formatting and enough words to satisfy the minimum content length requirement for blog publishing.";

        $createResponse = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession([
                'portal_admin_authenticated' => true,
                'portal_admin_user' => $admin->name,
                'portal_admin_user_id' => $admin->id,
                'portal_admin_role' => 'ADMIN_SUPER',
            ])
            ->post('/portal/admin/blog', [
                'title' => 'QA Blog Flow Post',
                'excerpt' => 'Flow test excerpt for blog QA.',
                'content' => $content,
                'is_published' => '1',
                'is_featured' => '0',
                'cover_image' => UploadedFile::fake()->create('cover.jpg', 120, 'image/jpeg'),
            ]);

        $createResponse
            ->assertStatus(302)
            ->assertSessionHas('portal_notice', 'Blog post created successfully.');

        /** @var BlogPost $post */
        $post = BlogPost::query()->where('title', 'QA Blog Flow Post')->firstOrFail();

        $this->assertNotEmpty($post->cover_image_path);
        $this->assertSame('blog/' . $post->id . '/cover.jpg', $post->cover_image_path);

        $publicResponse = $this->get('/blog/' . $post->slug);
        $publicResponse->assertOk();
        $publicResponse->assertSee('Section Heading');
        $publicResponse->assertSee('<strong>bold</strong>', false);
        $publicResponse->assertSee('/media/blog/' . $post->id . '/cover', false);

        $coverResponse = $this->get('/media/blog/' . $post->id . '/cover');
        $coverResponse->assertOk();
        $this->assertStringContainsString('image/', (string) $coverResponse->headers->get('Content-Type', ''));

        $deleteResponse = $this
            ->withoutMiddleware(VerifyCsrfToken::class)
            ->withSession([
                'portal_admin_authenticated' => true,
                'portal_admin_user' => $admin->name,
                'portal_admin_user_id' => $admin->id,
                'portal_admin_role' => 'ADMIN_SUPER',
            ])
            ->post('/portal/admin/blog/' . $post->id . '/delete');

        $deleteResponse
            ->assertStatus(302)
            ->assertSessionHas('portal_notice', 'Blog post deleted successfully.');

        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
        Storage::disk('public')->assertMissing('blog/' . $post->id . '/cover.jpg');
    }

    public function test_blog_cover_proxy_reads_legacy_url_style_cover_value_from_storage_disk(): void
    {
        Storage::fake('public');

        $post = BlogPost::query()->create([
            'title' => 'Legacy Cover URL Post',
            'slug' => 'legacy-cover-url-post',
            'excerpt' => 'Legacy cover path.',
            'content' => '## Heading' . PHP_EOL . PHP_EOL . 'Enough content to satisfy the minimum content length for rendering this post in tests.',
            'cover_image_path' => 'https://media.example.test/blog/77/cover.jpg',
            'is_published' => true,
            'published_at' => now(),
        ]);

        Storage::disk('public')->put('blog/77/cover.jpg', 'fake-image-binary');

        $response = $this->get('/media/blog/' . $post->id . '/cover');

        $response->assertOk();
        $response->assertHeader('Content-Type');
        $response->assertSee('fake-image-binary', false);
    }

    public function test_blog_helper_rewrites_full_url_cover_and_article_values_to_proxy_routes(): void
    {
        $this->assertSame(
            '/media/blog/42/cover',
            blogResolveCoverImageUrl('https://cdn.example.test/blog/42/cover.jpg')
        );

        $this->assertSame(
            '/media/blog/42/article/1',
            blogResolveCoverImageUrl('https://cdn.example.test/blog/42/article_1.webp?version=2')
        );
    }
}
