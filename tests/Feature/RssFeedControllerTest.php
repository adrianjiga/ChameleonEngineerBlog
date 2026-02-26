<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RssFeedControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_is_publicly_accessible(): void
    {
        $this->get(route('feed'))->assertOk();
    }

    public function test_feed_returns_rss_xml_content_type(): void
    {
        $response = $this->get(route('feed'));

        $this->assertStringContainsString('application/rss+xml', $response->headers->get('Content-Type'));
    }

    public function test_feed_contains_published_post_titles(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->published()->create(['title' => 'My RSS Post']);

        $response = $this->get(route('feed'));

        $response->assertSee('My RSS Post', false);
    }

    public function test_feed_does_not_contain_draft_posts(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->draft()->create(['title' => 'Hidden Draft']);

        $response = $this->get(route('feed'));

        $response->assertDontSee('Hidden Draft', false);
    }

    public function test_feed_contains_post_links(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->for($user)->published()->create();

        $response = $this->get(route('feed'));

        $response->assertSee(route('blog.show', $post->slug), false);
    }

    public function test_feed_is_valid_rss_xml(): void
    {
        $response = $this->get(route('feed'));

        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml);
        $this->assertEquals('rss', $xml->getName());
    }

    public function test_feed_is_limited_to_20_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(25)->for($user)->published()->create();

        $response = $this->get(route('feed'));
        $xml = simplexml_load_string($response->getContent());

        $this->assertCount(20, $xml->channel->item);
    }
}
