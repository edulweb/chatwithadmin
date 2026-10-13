<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminChatPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }
    public function test_public_registration_is_disabled(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Hacker Bob',
            'username' => 'hackerbob',
            'email' => 'bob@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_register_new_user(): void
    {
        $admin = User::where('is_admin', true)->first() ?? User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin);

        $testEmail = 'newuser_' . uniqid() . '@example.com';
        $testUsername = 'user_' . substr(uniqid(), 0, 10);

        $response = $this->postJson('/api/v1/admin/users', [
            'name' => 'Registered Member',
            'username' => $testUsername,
            'email' => $testEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => false,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.name', 'Registered Member');
        $this->assertDatabaseHas('users', ['email' => $testEmail]);
    }

    public function test_non_admin_cannot_register_user(): void
    {
        $user = User::where('is_admin', false)->first() ?? User::factory()->create(['is_admin' => false]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/admin/users', [
            'name' => 'Unauthorized Member',
            'username' => 'unauthorized',
            'email' => 'unauth@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_non_admin_can_only_see_admins_in_user_list(): void
    {
        $sarah = User::where('email', 'sarah@connectly.test')->first();
        Sanctum::actingAs($sarah);

        $response = $this->getJson('/api/v1/users');
        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        foreach ($data as $item) {
            $this->assertTrue($item['is_admin'], "Non-admin saw a user who is not an admin: " . $item['name']);
        }
    }

    public function test_admin_can_see_all_users_in_user_list(): void
    {
        $admin = User::where('email', 'alex@connectly.test')->first();
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/users');
        $response->assertStatus(200);

        $data = $response->json('data');
        $hasRegularUser = collect($data)->contains(fn ($u) => $u['is_admin'] === false);
        $this->assertTrue($hasRegularUser, "Admin should be able to see non-admin users in user list.");
    }

    public function test_non_admin_cannot_start_conversation_with_another_non_admin(): void
    {
        $sarah = User::where('email', 'sarah@connectly.test')->first();
        $david = User::where('email', 'david@connectly.test')->first();

        Sanctum::actingAs($sarah);

        $response = $this->postJson('/api/v1/conversations', [
            'user_id' => $david->id,
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment([
            'message' => 'Regular users are only permitted to chat with administrators.',
        ]);
    }

    public function test_non_admin_can_start_conversation_with_admin(): void
    {
        $sarah = User::where('email', 'sarah@connectly.test')->first();
        $admin = User::where('email', 'alex@connectly.test')->first();

        Sanctum::actingAs($sarah);

        $response = $this->postJson('/api/v1/conversations', [
            'user_id' => $admin->id,
        ]);

        $this->assertContains($response->status(), [200, 201]);
    }

    public function test_admin_can_start_conversation_with_anyone(): void
    {
        $admin = User::where('email', 'alex@connectly.test')->first();
        $david = User::where('email', 'david@connectly.test')->first();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/conversations', [
            'user_id' => $david->id,
        ]);

        $this->assertContains($response->status(), [200, 201]);
    }
}
