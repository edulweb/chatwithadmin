<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    private function setupConversation(User $user1, User $user2): Conversation
    {
        $pair = Conversation::makeCanonicalPair($user1->id, $user2->id);
        $conv = Conversation::create(['canonical_pair' => $pair, 'type' => 'direct']);
        ConversationMember::create(['conversation_id' => $conv->id, 'user_id' => $user1->id]);
        ConversationMember::create(['conversation_id' => $conv->id, 'user_id' => $user2->id]);
        return $conv;
    }

    public function test_member_can_send_message(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);
        $conv = $this->setupConversation($user1, $user2);

        $response = $this->actingAs($user1)->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'body' => 'Hello there!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.body', 'Hello there!')
            ->assertJsonPath('data.sender_id', $user1->id);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv->id,
            'sender_id' => $user1->id,
            'body' => 'Hello there!',
        ]);
    }

    public function test_non_member_cannot_send_message_idor(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);
        $user3 = User::factory()->create(['username' => 'user3']);
        $conv = $this->setupConversation($user1, $user2);

        $response = $this->actingAs($user3)->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'body' => 'Intruder message',
        ]);

        $response->assertStatus(403);
    }

    public function test_member_can_reply_to_message(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);
        $conv = $this->setupConversation($user1, $user2);

        $msg1 = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user1->id,
            'body' => 'Question?',
        ]);

        $response = $this->actingAs($user2)->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'body' => 'Answer!',
            'reply_to_id' => $msg1->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.reply_to_id', $msg1->id)
            ->assertJsonPath('data.reply_to.body', 'Question?');
    }

    public function test_sender_can_edit_own_message(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);
        $conv = $this->setupConversation($user1, $user2);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user1->id,
            'body' => 'Original text',
        ]);

        $response = $this->actingAs($user1)->patchJson("/api/v1/messages/{$msg->id}", [
            'body' => 'Updated text',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.body', 'Updated text');

        $this->assertDatabaseHas('messages', [
            'id' => $msg->id,
            'body' => 'Updated text',
        ]);
    }

    public function test_user_cannot_edit_other_users_message(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);
        $conv = $this->setupConversation($user1, $user2);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user1->id,
            'body' => 'Original text',
        ]);

        $response = $this->actingAs($user2)->patchJson("/api/v1/messages/{$msg->id}", [
            'body' => 'Hacked text',
        ]);

        $response->assertStatus(403);
    }

    public function test_sender_can_soft_delete_own_message(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);
        $conv = $this->setupConversation($user1, $user2);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user1->id,
            'body' => 'To be deleted',
        ]);

        $response = $this->actingAs($user1)->deleteJson("/api/v1/messages/{$msg->id}");
        $response->assertStatus(200);

        $this->assertSoftDeleted('messages', ['id' => $msg->id]);

        // Message should return "This message was deleted"
        $getRes = $this->actingAs($user2)->getJson("/api/v1/conversations/{$conv->id}/messages");
        $getRes->assertStatus(200)
            ->assertJsonPath('data.0.body', 'This message was deleted')
            ->assertJsonPath('data.0.is_deleted', true);
    }

    public function test_member_can_mark_messages_as_read(): void
    {
        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);
        $conv = $this->setupConversation($user1, $user2);

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user1->id,
            'body' => 'Please read me',
        ]);

        $response = $this->actingAs($user2)->postJson("/api/v1/conversations/{$conv->id}/read");
        $response->assertStatus(200);

        $this->assertDatabaseHas('conversation_members', [
            'conversation_id' => $conv->id,
            'user_id' => $user2->id,
            'last_read_message_id' => $msg->id,
        ]);
        $this->assertDatabaseHas('message_reads', [
            'message_id' => $msg->id,
            'user_id' => $user2->id,
        ]);
    }
}
