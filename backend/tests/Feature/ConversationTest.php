<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_direct_conversation(): void
    {
        $user1 = User::factory()->create(['username' => 'user1', 'is_admin' => true]);
        $user2 = User::factory()->create(['username' => 'user2']);

        $response = $this->actingAs($user1)->postJson('/api/v1/conversations', [
            'user_id' => $user2->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'direct');

        $this->assertDatabaseHas('conversations', [
            'canonical_pair' => Conversation::makeCanonicalPair($user1->id, $user2->id),
        ]);
        $this->assertDatabaseHas('conversation_members', [
            'user_id' => $user1->id,
        ]);
        $this->assertDatabaseHas('conversation_members', [
            'user_id' => $user2->id,
        ]);
    }

    public function test_duplicate_direct_conversation_returns_existing(): void
    {
        $user1 = User::factory()->create(['username' => 'user1', 'is_admin' => true]);
        $user2 = User::factory()->create(['username' => 'user2']);

        // First creation
        $res1 = $this->actingAs($user1)->postJson('/api/v1/conversations', [
            'user_id' => $user2->id,
        ]);
        $res1->assertStatus(201);
        $convId1 = $res1->json('data.id');

        // Reverse creation attempt by user2
        $res2 = $this->actingAs($user2)->postJson('/api/v1/conversations', [
            'user_id' => $user1->id,
        ]);
        $res2->assertStatus(200);
        $convId2 = $res2->json('data.id');

        $this->assertEquals($convId1, $convId2);
        $this->assertEquals(1, Conversation::count());
    }

    public function test_user_cannot_create_conversation_with_self(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);

        $response = $this->actingAs($user1)->postJson('/api/v1/conversations', [
            'user_id' => $user1->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_user_cannot_view_another_users_conversation_idor(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);
        $user3 = User::factory()->create(['username' => 'user3']);

        // Conv between 1 and 2
        $pair = Conversation::makeCanonicalPair($user1->id, $user2->id);
        $conv = Conversation::create(['canonical_pair' => $pair, 'type' => 'direct']);
        ConversationMember::create(['conversation_id' => $conv->id, 'user_id' => $user1->id]);
        ConversationMember::create(['conversation_id' => $conv->id, 'user_id' => $user2->id]);

        // User 3 attempts to view
        $response = $this->actingAs($user3)->getJson("/api/v1/conversations/{$conv->id}");
        $response->assertStatus(403);
    }

    public function test_blocked_user_cannot_create_conversation(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);

        // User 1 blocks User 2
        UserBlock::create(['user_id' => $user1->id, 'blocked_user_id' => $user2->id]);

        $response = $this->actingAs($user2)->postJson('/api/v1/conversations', [
            'user_id' => $user1->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_clear_all_chat_from_their_side(): void
    {
        $user1 = User::factory()->create(['username' => 'user1', 'is_admin' => true]);
        $user2 = User::factory()->create(['username' => 'user2']);

        $pair = Conversation::makeCanonicalPair($user1->id, $user2->id);
        $conv = Conversation::create(['canonical_pair' => $pair, 'type' => 'direct']);
        ConversationMember::create(['conversation_id' => $conv->id, 'user_id' => $user1->id]);
        ConversationMember::create(['conversation_id' => $conv->id, 'user_id' => $user2->id]);

        // Send two messages
        $msg1 = \App\Models\Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user1->id,
            'body' => 'Hello from user1',
        ]);
        $msg2 = \App\Models\Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user2->id,
            'body' => 'Hello from user2',
        ]);

        // User 2 clears chat from their side
        $clearRes = $this->actingAs($user2)->postJson("/api/v1/conversations/{$conv->id}/clear");
        $clearRes->assertStatus(200);

        // User 2 sees 0 messages
        $user2Messages = $this->actingAs($user2)->getJson("/api/v1/conversations/{$conv->id}/messages");
        $user2Messages->assertStatus(200)->assertJsonCount(0, 'data');

        // User 1 still sees both messages intact
        $user1Messages = $this->actingAs($user1)->getJson("/api/v1/conversations/{$conv->id}/messages");
        $user1Messages->assertStatus(200)->assertJsonCount(2, 'data');

        // Conversations list: User 2 sees latest_message null, User 1 sees latest_message as msg2
        $user2Convs = $this->actingAs($user2)->getJson('/api/v1/conversations');
        $this->assertNull($user2Convs->json('data.0.latest_message'));

        $user1Convs = $this->actingAs($user1)->getJson('/api/v1/conversations');
        $this->assertEquals($msg2->id, $user1Convs->json('data.0.latest_message.id'));

        // A new message arrives
        $msg3 = \App\Models\Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user1->id,
            'body' => 'New message after clear',
        ]);

        // User 2 now sees only msg3
        $user2MessagesAfter = $this->actingAs($user2)->getJson("/api/v1/conversations/{$conv->id}/messages");
        $user2MessagesAfter->assertStatus(200)->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $msg3->id);

        // User 1 sees all 3 messages
        $user1MessagesAfter = $this->actingAs($user1)->getJson("/api/v1/conversations/{$conv->id}/messages");
        $user1MessagesAfter->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_typing_endpoint_validates_and_broadcasts(): void
    {
        $user1 = User::factory()->create(['username' => 'user1', 'is_admin' => true]);
        $user2 = User::factory()->create(['username' => 'user2']);

        $pair = Conversation::makeCanonicalPair($user1->id, $user2->id);
        $conv = Conversation::create(['canonical_pair' => $pair, 'type' => 'direct']);
        ConversationMember::create(['conversation_id' => $conv->id, 'user_id' => $user1->id]);
        ConversationMember::create(['conversation_id' => $conv->id, 'user_id' => $user2->id]);

        // Valid is_typing true
        $res = $this->actingAs($user1)->postJson("/api/v1/conversations/{$conv->id}/typing", [
            'is_typing' => true,
        ]);
        $res->assertStatus(200)
            ->assertJsonPath('is_typing', true);

        // Valid is_typing false
        $resFalse = $this->actingAs($user1)->postJson("/api/v1/conversations/{$conv->id}/typing", [
            'is_typing' => false,
        ]);
        $resFalse->assertStatus(200)
            ->assertJsonPath('is_typing', false);

        // Validation fails if non-boolean passed
        $resInvalid = $this->actingAs($user1)->postJson("/api/v1/conversations/{$conv->id}/typing", [
            'is_typing' => 'not-a-boolean-value',
        ]);
        $resInvalid->assertStatus(422)
            ->assertJsonValidationErrors(['is_typing']);
    }
}
