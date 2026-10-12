<?php

namespace Tests\Feature;

use App\Events\UserDisabled;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDisableUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_admin_can_disable_user_and_revokes_tokens_and_broadcasts_event(): void
    {
        Event::fake([UserDisabled::class]);

        $admin = User::where('is_admin', true)->first();
        $targetUser = User::where('is_admin', false)->first();

        // Create a personal access token for target user
        $targetToken = $targetUser->createToken('test_token')->plainTextToken;
        $this->assertCount(1, $targetUser->tokens);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/admin/users/{$targetUser->id}/disable");

        $response->assertStatus(200);
        $this->assertTrue($targetUser->fresh()->isDisabled());
        $this->assertCount(0, $targetUser->fresh()->tokens);

        Event::assertDispatched(UserDisabled::class, function ($event) use ($targetUser) {
            return $event->userId === $targetUser->id;
        });
    }

    public function test_disabled_user_is_blocked_by_middleware(): void
    {
        $targetUser = User::where('is_admin', false)->first();
        $targetUser->update(['is_disabled' => true]);

        Sanctum::actingAs($targetUser);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(403);
        $response->assertJson([
            'account_disabled' => true,
        ]);
    }

    public function test_disabled_user_cannot_login(): void
    {
        $targetUser = User::where('is_admin', false)->first();
        $targetUser->update(['is_disabled' => true]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => $targetUser->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
        $response->assertJsonValidationErrors(['login']);
    }

    public function test_admin_can_enable_user(): void
    {
        $admin = User::where('is_admin', true)->first();
        $targetUser = User::where('is_admin', false)->first();
        $targetUser->update(['is_disabled' => true]);

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/admin/users/{$targetUser->id}/enable");

        $response->assertStatus(200);
        $this->assertFalse($targetUser->fresh()->isDisabled());
    }

    public function test_non_admin_cannot_disable_user(): void
    {
        $user1 = User::where('is_admin', false)->first();
        $user2 = User::where('is_admin', false)->where('id', '!=', $user1->id)->first();

        Sanctum::actingAs($user1);

        $response = $this->postJson("/api/v1/admin/users/{$user2->id}/disable");

        $response->assertStatus(403);
    }

    public function test_admin_cannot_disable_self(): void
    {
        $admin = User::where('is_admin', true)->first();

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/admin/users/{$admin->id}/disable");

        $response->assertStatus(422);
    }

    public function test_user_cannot_block_administrator(): void
    {
        $admin = User::where('is_admin', true)->first();
        $user = User::where('is_admin', false)->first();

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/users/{$admin->id}/block");

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Administrators cannot be blocked.',
        ]);
    }
}
