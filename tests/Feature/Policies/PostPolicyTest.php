<?php

namespace Tests\Feature\Policies;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(bool $isAdmin = false): User
    {
        return User::factory()->create(['is_admin' => $isAdmin]);
    }

    private function makePost(User $owner): Post
    {
        return Post::create([
            'user_id' => $owner->id,
            'title' => 'Test Post',
            'content' => 'Content',
            'status' => PostStatus::Draft,
        ]);
    }

    // viewAny

    public function test_any_authenticated_user_can_view_any_posts(): void
    {
        $user = $this->makeUser();

        $this->assertTrue($user->can('viewAny', Post::class));
    }

    // create

    public function test_any_authenticated_user_can_create_posts(): void
    {
        $user = $this->makeUser();

        $this->assertTrue($user->can('create', Post::class));
    }

    // view

    public function test_owner_can_view_their_post(): void
    {
        $user = $this->makeUser();
        $post = $this->makePost($user);

        $this->assertTrue($user->can('view', $post));
    }

    public function test_admin_can_view_any_post(): void
    {
        $admin = $this->makeUser(isAdmin: true);
        $owner = $this->makeUser();
        $post = $this->makePost($owner);

        $this->assertTrue($admin->can('view', $post));
    }

    public function test_non_owner_cannot_view_others_post(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $post = $this->makePost($owner);

        $this->assertFalse($other->can('view', $post));
    }

    // update

    public function test_owner_can_update_their_post(): void
    {
        $user = $this->makeUser();
        $post = $this->makePost($user);

        $this->assertTrue($user->can('update', $post));
    }

    public function test_admin_can_update_any_post(): void
    {
        $admin = $this->makeUser(isAdmin: true);
        $owner = $this->makeUser();
        $post = $this->makePost($owner);

        $this->assertTrue($admin->can('update', $post));
    }

    public function test_non_owner_cannot_update_others_post(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $post = $this->makePost($owner);

        $this->assertFalse($other->can('update', $post));
    }

    // delete

    public function test_owner_can_delete_their_post(): void
    {
        $user = $this->makeUser();
        $post = $this->makePost($user);

        $this->assertTrue($user->can('delete', $post));
    }

    public function test_admin_can_delete_any_post(): void
    {
        $admin = $this->makeUser(isAdmin: true);
        $owner = $this->makeUser();
        $post = $this->makePost($owner);

        $this->assertTrue($admin->can('delete', $post));
    }

    public function test_non_owner_cannot_delete_others_post(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $post = $this->makePost($owner);

        $this->assertFalse($other->can('delete', $post));
    }

    // restore

    public function test_owner_can_restore_their_post(): void
    {
        $user = $this->makeUser();
        $post = $this->makePost($user);

        $this->assertTrue($user->can('restore', $post));
    }

    public function test_admin_can_restore_any_post(): void
    {
        $admin = $this->makeUser(isAdmin: true);
        $owner = $this->makeUser();
        $post = $this->makePost($owner);

        $this->assertTrue($admin->can('restore', $post));
    }

    public function test_non_owner_cannot_restore_others_post(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $post = $this->makePost($owner);

        $this->assertFalse($other->can('restore', $post));
    }

    // forceDelete

    public function test_admin_can_force_delete_any_post(): void
    {
        $admin = $this->makeUser(isAdmin: true);
        $owner = $this->makeUser();
        $post = $this->makePost($owner);

        $this->assertTrue($admin->can('forceDelete', $post));
    }

    public function test_owner_cannot_force_delete_their_post(): void
    {
        $user = $this->makeUser();
        $post = $this->makePost($user);

        $this->assertFalse($user->can('forceDelete', $post));
    }

    public function test_non_owner_cannot_force_delete_others_post(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $post = $this->makePost($owner);

        $this->assertFalse($other->can('forceDelete', $post));
    }
}
