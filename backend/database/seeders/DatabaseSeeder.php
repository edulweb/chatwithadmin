<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $alex = User::updateOrCreate(
            ['email' => 'alex@chatbox.test'],
            [
                'name' => 'Alex Morgan (Admin)',
                'username' => 'alex',
                'password' => Hash::make('password123'),
                'is_admin' => true,
                'is_active' => true,
                'email_verified_at' => now(),
                'last_seen_at' => now(),
            ]
        );

        $sarah = User::updateOrCreate(
            ['email' => 'sarah@chatbox.test'],
            [
                'name' => 'Sarah Connor',
                'username' => 'sarah',
                'password' => Hash::make('password123'),
                'is_admin' => false,
                'is_active' => true,
                'email_verified_at' => now(),
                'last_seen_at' => now()->subMinutes(1),
            ]
        );

        // Demo direct conversation between Alex and Sarah
        $pair = Conversation::makeCanonicalPair($alex->id, $sarah->id);
        $conv = Conversation::firstOrCreate(
            ['canonical_pair' => $pair],
            [
                'type' => 'direct',
                'created_by' => $alex->id,
            ]
        );

        ConversationMember::firstOrCreate(
            ['conversation_id' => $conv->id, 'user_id' => $alex->id],
            ['joined_at' => now()]
        );

        ConversationMember::firstOrCreate(
            ['conversation_id' => $conv->id, 'user_id' => $sarah->id],
            ['joined_at' => now()]
        );

        if ($conv->messages()->count() === 0) {
            $msg1 = Message::create([
                'conversation_id' => $conv->id,
                'sender_id' => $alex->id,
                'type' => 'text',
                'body' => 'Hey Sarah! Welcome to Connectly. 🚀',
                'created_at' => now()->subMinutes(10),
            ]);

            $msg2 = Message::create([
                'conversation_id' => $conv->id,
                'sender_id' => $sarah->id,
                'type' => 'text',
                'body' => 'Hi Alex! The interface looks incredibly slick and snappy.',
                'reply_to_id' => $msg1->id,
                'created_at' => now()->subMinutes(8),
            ]);

            $msg3 = Message::create([
                'conversation_id' => $conv->id,
                'sender_id' => $alex->id,
                'type' => 'text',
                'body' => 'Real-time WebSockets with Laravel Reverb and Vue 3 work flawlessly!',
                'created_at' => now()->subMinutes(5),
            ]);

            // Mark read
            ConversationMember::where('conversation_id', $conv->id)
                ->where('user_id', $alex->id)
                ->update(['last_read_message_id' => $msg3->id]);

            ConversationMember::where('conversation_id', $conv->id)
                ->where('user_id', $sarah->id)
                ->update(['last_read_message_id' => $msg3->id]);
        }
    }
}
