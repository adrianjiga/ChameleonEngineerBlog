<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_is_publicly_accessible(): void
    {
        $this->get(route('sitemap'))->assertOk();
    }

    public function test_sitemap_returns_xml_content_type(): void
    {
        $response = $this->get(route('sitemap'));

        $this->assertStringContainsString('application/xml', $response->headers->get('Content-Type'));
    }

    public function test_sitemap_contains_published_post_urls(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->published()->create();

        $response = $this->get(route('sitemap'));

        $response->assertSee(route('blog.show', $post->slug), false);
    }

    public function test_sitemap_contains_category_urls(): void
    {
        $category = Category::factory()->create();

        $response = $this->get(route('sitemap'));

        $response->assertSee(route('blog.index', ['category' => $category->slug]), false);
    }

    public function test_sitemap_contains_home_and_blog_urls(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertSee(route('home'), false);
        $response->assertSee(route('blog.index'), false);
    }

    public function test_sitemap_is_valid_xml(): void
    {
        $response = $this->get(route('sitemap'));

        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml);
    }

    public function test_sitemap_lastmod_is_present_for_published_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(2)->for($user)->published()->create();

        $response = $this->get(route('sitemap'));

        $response->assertSee('<lastmod>', false);
    }

    public function test_sitemap_does_not_contain_draft_post_urls(): void
    {
        $user = User::factory()->create();
        $draft = Post::factory()->for($user)->draft()->create();
        $unpublished = Post::factory()->for($user)->draft()->create();

        $response = $this->get(route('sitemap'));

        $response->assertDontSee(route('blog.show', $draft->slug), false);
        $response->assertDontSee(route('blog.show', $unpublished->slug), false);
    }

    public function test_sitemap_root_element_has_correct_xmlns_declaration(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertSee('xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"', false);
    }
}
