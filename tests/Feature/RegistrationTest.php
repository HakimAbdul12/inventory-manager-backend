<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles exist
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'dealer']);
    }

    public function test_user_can_register_with_extra_fields(): void
    {
        $response = $this->postJson('/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'company_name' => 'Test Company',
            'phone' => '1234567890',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'dealer_code',
                        'company_name',
                        'role',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'company_name' => 'Test Company',
            'phone' => '1234567890',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user->dealer_code);
        $this->assertTrue($user->hasRole('dealer'));
    }

    public function test_dealer_code_is_unique(): void
    {
        $user1 = User::create([
            'name' => 'User 1',
            'email' => 'user1@example.com',
            'password' => 'password',
        ]);

        $user2 = User::create([
            'name' => 'User 2',
            'email' => 'user2@example.com',
            'password' => 'password',
        ]);

        $this->assertNotNull($user1->dealer_code);
        $this->assertNotNull($user2->dealer_code);
        $this->assertNotEquals($user1->dealer_code, $user2->dealer_code);
    }
}
