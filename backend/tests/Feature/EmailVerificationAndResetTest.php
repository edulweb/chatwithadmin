<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AccountActivationNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyNewEmailNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmailVerificationAndResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_created_user_receives_activation_notification_and_cannot_login_until_activated(): void
    {
        Notification::fake();

        $admin = User::where('is_admin', true)->first();
        Sanctum::actingAs($admin);

        $email = 'inactive_' . uniqid() . '@example.com';
        $username = 'inactive_' . substr(uniqid(), 0, 8);

        $response = $this->postJson('/api/v1/admin/users', [
            'name' => 'Inactive User',
            'username' => $username,
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_admin' => false,
            'require_activation' => true,
        ]);

        $response->assertStatus(201);
        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->isActive());
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->activation_token);

        Notification::assertSentTo($user, AccountActivationNotification::class);

        // Try logging in before activation -> should fail with 403
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'login' => $email,
            'password' => 'password123',
        ]);
        $loginRes->assertStatus(403);
        $loginRes->assertJsonValidationErrors(['login']);

        // Activate account using token
        $activateRes = $this->postJson('/api/v1/auth/activate', [
            'token' => $user->activation_token,
        ]);
        $activateRes->assertStatus(200);

        $user->refresh();
        $this->assertTrue($user->isActive());
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->activation_token);

        // Now login should succeed
        $loginSuccess = $this->postJson('/api/v1/auth/login', [
            'login' => $email,
            'password' => 'password123',
        ]);
        $loginSuccess->assertStatus(200);
        $loginSuccess->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_forgot_password_sends_notification_and_allows_reset(): void
    {
        Notification::fake();

        $user = User::where('is_admin', false)->first();

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200);

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $record = DB::table('password_reset_tokens')->where('email', $user->email)->first();
            return $record && Hash::check($notification->token, $record->token);
        });

        // Retrieve token from notification callback
        $token = null;
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use (&$token) {
            $token = $notification->token;
            return true;
        });

        $this->assertNotNull($token);

        // Reset password
        $resetRes = $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $resetRes->assertStatus(200);

        // Verify user can login with new password
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'login' => $user->email,
            'password' => 'newpassword123',
        ]);
        $loginRes->assertStatus(200);
    }

    public function test_changing_email_requires_verification_and_updates_email(): void
    {
        Notification::fake();

        $user = User::where('is_admin', false)->first();
        Sanctum::actingAs($user);

        $oldEmail = $user->email;
        $newEmail = 'updated_' . uniqid() . '@example.com';

        $response = $this->patchJson('/api/v1/me', [
            'name' => $user->name,
            'email' => $newEmail,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['email_verification_pending' => true]);

        $user->refresh();
        $this->assertEquals($oldEmail, $user->email);
        $this->assertEquals($newEmail, $user->pending_email);
        $this->assertNotNull($user->activation_token);

        Notification::assertSentTo($user, VerifyNewEmailNotification::class, function ($notification) use ($newEmail, $user) {
            return $notification->newEmail === $newEmail && $notification->token === $user->activation_token;
        });

        // Verify email change
        $verifyRes = $this->postJson('/api/v1/auth/verify-email-change', [
            'token' => $user->activation_token,
        ]);

        $verifyRes->assertStatus(200);

        $user->refresh();
        $this->assertEquals($newEmail, $user->email);
        $this->assertNull($user->pending_email);
        $this->assertNull($user->activation_token);
    }
}
