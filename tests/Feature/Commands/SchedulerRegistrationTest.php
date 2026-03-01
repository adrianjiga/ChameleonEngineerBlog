<?php

namespace Tests\Feature\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_publish_scheduled_posts_is_registered_in_scheduler(): void
    {
        $schedule = $this->app->make(Schedule::class);
        $events = $schedule->events();

        $commandNames = collect($events)->map(fn ($event) => $event->command ?? '')->filter();
        $commandSignatures = collect($events)->map(fn ($event) => $event->description ?? '')->filter();

        $hasPublishCommand = collect($events)->contains(function ($event) {
            return str_contains($event->command ?? '', 'publish-scheduled')
                || str_contains($event->command ?? '', 'PublishScheduledPosts');
        });

        $this->assertTrue($hasPublishCommand, 'posts:publish-scheduled command should be registered in the scheduler.');
    }

    public function test_cleanup_orphaned_images_is_registered_in_scheduler(): void
    {
        $schedule = $this->app->make(Schedule::class);
        $events = $schedule->events();

        $hasCleanupCommand = collect($events)->contains(function ($event) {
            return str_contains($event->command ?? '', 'cleanup-images')
                || str_contains($event->command ?? '', 'CleanupOrphanedImages');
        });

        $this->assertTrue($hasCleanupCommand, 'posts:cleanup-images command should be registered in the scheduler.');
    }
}
