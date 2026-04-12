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

    private function fakeJpegUpload(string $name = 'cover.jpg'): UploadedFile
    {
        $jpegBytes = base64_decode('/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBxAQEBAQEBAVFRUVFRUVFRUVFRUVFRUVFhUVFRUYHSggGBolGxUVITEhJSkrLi4uFx8zODMsNygtLisBCgoKDg0OGhAQGi0fHyUtLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLf/AABEIAAEAAgMBIgACEQEDEQH/xAAVAAEBAAAAAAAAAAAAAAAAAAAAAf/EABQBAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhADEAAAAdM//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQL/xAAVEQEBAAAAAAAAAAAAAAAAAAABAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAEP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAEP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAEP/aAAgBAQABPyF//9k=', true);

        return UploadedFile::fake()->createWithContent($name, $jpegBytes !== false ? $jpegBytes : 'jpeg');
    }

    private function fakeBlogMediaDisks(): void
    {
        $portalMediaDisk = trim((string) config('filesystems.portal_media_disk', 'public'));
        if ($portalMediaDisk === '') {
            $portalMediaDisk = 'public';
        }

        foreach (array_values(array_unique([$portalMediaDisk, 'public'])) as $diskName) {
            Storage::fake($diskName);
        }
    }

    public function test_admin_can_create_render_and_delete_blog_post_with_cover_image(): void
    {
        $this->fakeBlogMediaDisks();

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
                'cover_image' => $this->fakeJpegUpload('cover.jpg'),
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
        $resolvedCoverUrl = blogResolveCoverImageUrl((string) $post->cover_image_path);
        $publicResponse->assertSee($resolvedCoverUrl, false);

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
        $this->fakeBlogMediaDisks();

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

    public function test_blog_helper_resolves_full_url_cover_and_article_values_to_managed_media_urls(): void
    {
        $coverResolved = blogResolveCoverImageUrl('https://cdn.example.test/blog/42/cover.jpg');
        $this->assertStringContainsString('/storage/blog/42/cover.jpg', $coverResolved);

        $articleResolved = blogResolveCoverImageUrl('https://cdn.example.test/blog/42/article_1.webp?version=2');
        $this->assertStringContainsString('/storage/blog/42/article_1.webp', $articleResolved);
    }
}
