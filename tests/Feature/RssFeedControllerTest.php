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

    public function test_feed_channel_title_matches_app_name(): void
    {
        $response = $this->get(route('feed'));

        $response->assertSee('<title>'.config('app.name').'</title>', false);
    }

    public function test_feed_items_are_ordered_by_published_at_descending(): void
    {
        $user = User::factory()->create();
        Post::factory()->for($user)->published()->create([
            'title' => 'Yesterday Post',
            'published_at' => now()->subDay(),
        ]);
        Post::factory()->for($user)->published()->create([
            'title' => 'Last Week Post',
            'published_at' => now()->subWeek(),
        ]);
        Post::factory()->for($user)->published()->create([
            'title' => 'Today Post',
            'published_at' => now(),
        ]);

        $response = $this->get(route('feed'));
        $xml = simplexml_load_string($response->getContent());

        $titles = [];
        foreach ($xml->channel->item as $item) {
            $titles[] = (string) $item->title;
        }

        $this->assertSame('Today Post', $titles[0]);
    }

    public function test_feed_item_description_contains_excerpt(): void
    {
        $user = User::factory()->create();
        Post::factory()->for($user)->published()->create([
            'excerpt' => 'This is my test excerpt',
        ]);

        $response = $this->get(route('feed'));

        $response->assertSee('This is my test excerpt', false);
    }

    public function test_feed_returns_200_when_no_posts_exist(): void
    {
        $this->get(route('feed'))->assertOk();
    }

    public function test_feed_items_contain_pub_date_element(): void
    {
        $user = User::factory()->create();
        Post::factory()->for($user)->published()->create(['published_at' => now()]);

        $response = $this->get(route('feed'));

        $response->assertSee('<pubDate>', false);
    }

    public function test_feed_items_contain_unique_guid_element(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(2)->for($user)->published()->create();

        $response = $this->get(route('feed'));
        $xml = simplexml_load_string($response->getContent());

        $guids = [];
        foreach ($xml->channel->item as $item) {
            $guids[] = (string) $item->guid;
        }

        $this->assertSame(count($guids), count(array_unique($guids)));
    }
}
