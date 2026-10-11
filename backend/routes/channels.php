<?php

use App\Models\ConversationMember;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    return ConversationMember::where('conversation_id', $conversationId)
        ->where('user_id', $user->id)
        ->exists();
}, ['guards' => ['web', 'sanctum']]);

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
}, ['guards' => ['web', 'sanctum']]);

Broadcast::channel('online', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'username' => $user->username,
        'avatar_url' => $user->avatar_url,
    ];
}, ['guards' => ['web', 'sanctum']]);
