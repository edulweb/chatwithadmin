<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastingConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_broadcasting_config_endpoint_returns_success_and_driver(): void
    {
        $response = $this->getJson('/api/v1/broadcasting/config');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'driver',
                'reverb' => ['key', 'host', 'port', 'scheme'],
                'pusher' => ['key', 'cluster', 'host', 'port', 'scheme'],
            ]);

        // Assure secrets are never leaked
        $json = $response->json();
        $this->assertArrayNotHasKey('secret', $json['reverb']);
        $this->assertArrayNotHasKey('secret', $json['pusher']);
    }

    public function test_broadcasting_config_reflects_reverb_driver(): void
    {
        config(['broadcasting.default' => 'reverb']);

        $response = $this->getJson('/api/v1/broadcasting/config');

        $response->assertStatus(200);
        $this->assertSame('reverb', $response->json('driver'));
    }

    public function test_broadcasting_config_reflects_pusher_driver(): void
    {
        config(['broadcasting.default' => 'pusher']);

        $response = $this->getJson('/api/v1/broadcasting/config');

        $response->assertStatus(200);
        $this->assertSame('pusher', $response->json('driver'));
    }

    public function test_pusher_broadcaster_can_be_resolved_with_configured_driver(): void
    {
        $broadcaster = \Illuminate\Support\Facades\Broadcast::connection('pusher');
        $this->assertInstanceOf(\Illuminate\Broadcasting\Broadcasters\PusherBroadcaster::class, $broadcaster);
    }

    public function test_authenticated_user_can_authenticate_presence_channel_with_pusher(): void
    {
        config(['broadcasting.default' => 'pusher']);
        \Illuminate\Support\Facades\Broadcast::forgetDrivers();
        \Illuminate\Support\Facades\Broadcast::setDefaultDriver('pusher');
        require base_path('routes/channels.php');
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'presence-online',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['auth', 'channel_data']);
    }
}
