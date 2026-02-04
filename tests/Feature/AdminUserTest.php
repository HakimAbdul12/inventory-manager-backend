<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'dealer']);
    }

    public function test_admin_can_list_users()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        User::factory()->count(3)->create();

        $response = $this->actingAs($admin)
            ->getJson('/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'email', 'roles']
                ],
                'current_page',
            ]);
    }

    public function test_non_admin_cannot_list_users()
    {
        $user = User::factory()->create();
        $user->assignRole('dealer');

        $response = $this->actingAs($user)
            ->getJson('/admin/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_block_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();

        $response = $this->actingAs($admin)
            ->postJson("/admin/users/{$user->id}/block");

        $response->assertStatus(200);
        $this->assertNotNull($user->fresh()->blocked_at);
    }

    public function test_admin_can_unblock_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create(['blocked_at' => now()]);

        $response = $this->actingAs($admin)
            ->postJson("/admin/users/{$user->id}/block");

        $response->assertStatus(200);
        $this->assertNull($user->fresh()->blocked_at);
    }

    public function test_admin_cannot_block_another_admin()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $otherAdmin = User::factory()->create();
        $otherAdmin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->postJson("/admin/users/{$otherAdmin->id}/block");

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();

        $response = $this->actingAs($admin)
            ->deleteJson("/admin/users/{$user->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        // I will check User model, but for now assertDatabaseMissing is safer if no SoftDeletes.
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
