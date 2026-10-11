<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileAttachmentTest extends TestCase
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

    public function test_member_can_upload_file_attachment(): void
    {
        Storage::fake('chat_private');

        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);
        $conv = $this->setupConversation($user1, $user2);

        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user1)->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'body' => 'Here is the report',
            'file' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'mixed')
            ->assertJsonPath('data.attachments.0.original_name', 'document.pdf');

        $this->assertDatabaseHas('message_attachments', [
            'original_name' => 'document.pdf',
            'disk' => 'chat_private',
            'mime_type' => 'application/pdf',
        ]);
    }

    public function test_authorized_user_can_download_attachment(): void
    {
        Storage::fake('chat_private');

        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);
        $conv = $this->setupConversation($user1, $user2);

        $path = "conversations/{$conv->id}/secret.txt";
        Storage::disk('chat_private')->put($path, 'Confidential content');

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user1->id,
            'type' => 'file',
        ]);

        $attachment = MessageAttachment::create([
            'message_id' => $msg->id,
            'disk' => 'chat_private',
            'path' => $path,
            'original_name' => 'secret.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 20,
            'scan_status' => 'clean',
        ]);

        $response = $this->actingAs($user2)->get("/api/v1/attachments/{$attachment->id}/download");
        $response->assertStatus(200);
    }

    public function test_unauthorized_user_cannot_download_attachment_idor(): void
    {
        Storage::fake('chat_private');

        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);
        $user3 = User::factory()->create(['username' => 'user3']);
        $conv = $this->setupConversation($user1, $user2);

        $path = "conversations/{$conv->id}/secret.txt";
        Storage::disk('chat_private')->put($path, 'Confidential content');

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user1->id,
            'type' => 'file',
        ]);

        $attachment = MessageAttachment::create([
            'message_id' => $msg->id,
            'disk' => 'chat_private',
            'path' => $path,
            'original_name' => 'secret.txt',
            'mime_type' => 'text/plain',
            'size_bytes' => 20,
            'scan_status' => 'clean',
        ]);

        // User 3 (not a member) attempts to download
        $response = $this->actingAs($user3)->getJson("/api/v1/attachments/{$attachment->id}/download");
        $response->assertStatus(403);
    }

    public function test_member_can_upload_all_file_types(): void
    {
        Storage::fake('chat_private');

        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);
        $conv = $this->setupConversation($user1, $user2);

        // Test video file upload
        $videoFile = UploadedFile::fake()->create('clip.mp4', 2048, 'video/mp4');
        $responseVideo = $this->actingAs($user1)->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'file' => $videoFile,
        ]);
        $responseVideo->assertStatus(201)
            ->assertJsonPath('data.attachments.0.is_video', true)
            ->assertJsonPath('data.attachments.0.original_name', 'clip.mp4');

        // Test audio file upload
        $audioFile = UploadedFile::fake()->create('song.mp3', 1024, 'audio/mpeg');
        $responseAudio = $this->actingAs($user1)->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'file' => $audioFile,
        ]);
        $responseAudio->assertStatus(201)
            ->assertJsonPath('data.attachments.0.is_audio', true)
            ->assertJsonPath('data.attachments.0.original_name', 'song.mp3');

        // Test arbitrary binary/executable file upload
        $binaryFile = UploadedFile::fake()->create('installer.exe', 512, 'application/octet-stream');
        $responseBinary = $this->actingAs($user1)->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'file' => $binaryFile,
        ]);
        $responseBinary->assertStatus(201)
            ->assertJsonPath('data.attachments.0.original_name', 'installer.exe');
    }

    public function test_video_and_audio_support_inline_preview_and_streaming(): void
    {
        Storage::fake('chat_private');

        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);
        $conv = $this->setupConversation($user1, $user2);

        $path = "conversations/{$conv->id}/sample.mp4";
        Storage::disk('chat_private')->put($path, 'fake-mp4-data');

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => $user1->id,
            'type' => 'file',
        ]);

        $attachment = MessageAttachment::create([
            'message_id' => $msg->id,
            'disk' => 'chat_private',
            'path' => $path,
            'original_name' => 'sample.mp4',
            'mime_type' => 'video/mp4',
            'size_bytes' => 13,
            'scan_status' => 'clean',
        ]);

        // Preview request for video
        $response = $this->actingAs($user2)->get("/api/v1/attachments/{$attachment->id}/download?preview=1");
        $response->assertStatus(200);
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition') ?? '');
        $this->assertEquals('video/mp4', $response->headers->get('Content-Type'));
        $this->assertEquals('bytes', $response->headers->get('Accept-Ranges'));
    }

    public function test_file_upload_rejects_exceeding_max_size(): void
    {
        Storage::fake('chat_private');

        $user1 = User::factory()->create(['username' => 'user1']);
        $user2 = User::factory()->create(['username' => 'user2']);
        $conv = $this->setupConversation($user1, $user2);

        // Exceeding 100 MiB (102401 KB)
        $oversizedFile = UploadedFile::fake()->create('huge.zip', 102401, 'application/zip');

        $response = $this->actingAs($user1)->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'file' => $oversizedFile,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }
}
