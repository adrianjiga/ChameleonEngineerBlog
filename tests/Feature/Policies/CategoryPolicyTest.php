<?php

namespace Tests\Feature\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(bool $isAdmin = false): User
    {
        return User::factory()->create(['is_admin' => $isAdmin]);
    }

    private function makeCategory(): Category
    {
        return Category::create(['name' => 'Tech']);
    }

    // viewAny

    public function test_any_authenticated_user_can_view_any_categories(): void
    {
        $user = $this->makeUser();

        $this->assertTrue($user->can('viewAny', Category::class));
    }

    // view

    public function test_any_authenticated_user_can_view_a_category(): void
    {
        $user = $this->makeUser();
        $category = $this->makeCategory();

        $this->assertTrue($user->can('view', $category));
    }

    // create

    public function test_admin_can_create_categories(): void
    {
        $admin = $this->makeUser(isAdmin: true);

        $this->assertTrue($admin->can('create', Category::class));
    }

    public function test_non_admin_cannot_create_categories(): void
    {
        $user = $this->makeUser();

        $this->assertFalse($user->can('create', Category::class));
    }

    // update

    public function test_admin_can_update_categories(): void
    {
        $admin = $this->makeUser(isAdmin: true);
        $category = $this->makeCategory();

        $this->assertTrue($admin->can('update', $category));
    }

    public function test_non_admin_cannot_update_categories(): void
    {
        $user = $this->makeUser();
        $category = $this->makeCategory();

        $this->assertFalse($user->can('update', $category));
    }

    // delete

    public function test_admin_can_delete_categories(): void
    {
        $admin = $this->makeUser(isAdmin: true);
        $category = $this->makeCategory();

        $this->assertTrue($admin->can('delete', $category));
    }

    public function test_non_admin_cannot_delete_categories(): void
    {
        $user = $this->makeUser();
        $category = $this->makeCategory();

        $this->assertFalse($user->can('delete', $category));
    }

    // forceDelete

    public function test_admin_can_force_delete_categories(): void
    {
        $admin = $this->makeUser(isAdmin: true);
        $category = $this->makeCategory();

        $this->assertTrue($admin->can('forceDelete', $category));
    }

    public function test_non_admin_cannot_force_delete_categories(): void
    {
        $user = $this->makeUser();
        $category = $this->makeCategory();

        $this->assertFalse($user->can('forceDelete', $category));
    }
}
