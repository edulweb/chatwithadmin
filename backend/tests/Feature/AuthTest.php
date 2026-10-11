<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Alice Wonder',
            'username' => 'alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => ['id', 'name', 'username', 'email'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'username' => 'alice',
            'email' => 'alice@example.com',
        ]);
    }

    public function test_registration_validation_fails_on_duplicate_username(): void
    {
        User::factory()->create([
            'username' => 'existinguser',
            'email' => 'other@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Another User',
            'username' => 'existinguser',
            'email' => 'new@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_user_can_login_with_email_or_username(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'username' => 'johnny',
            'password' => Hash::make('password123'),
        ]);

        // Login with email
        $resEmail = $this->postJson('/api/v1/auth/login', [
            'login' => 'john@example.com',
            'password' => 'password123',
        ]);
        $resEmail->assertStatus(200)->assertJsonStructure(['data' => ['token']]);

        // Login with username
        $resUsername = $this->postJson('/api/v1/auth/login', [
            'login' => 'johnny',
            'password' => 'password123',
        ]);
        $resUsername->assertStatus(200)->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'john@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_access_me_and_logout(): void
    {
        $user = User::factory()->create(['username' => 'testuser']);
        $token = $user->createToken('test')->plainTextToken;

        // Me
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/me');
        $response->assertStatus(200)
            ->assertJsonPath('data.username', 'testuser');

        // Logout
        $logoutRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');
        $logoutRes->assertStatus(200);

        // Tokens revoked
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/me');
        $response->assertStatus(401);
    }
}
