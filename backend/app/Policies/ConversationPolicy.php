<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Determine whether the user can view the conversation.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->members()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can send messages in the conversation.
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        if (!$this->view($user, $conversation)) {
            return false;
        }

        // Check if there is an active block between the two users
        if ($conversation->type === 'direct') {
            $otherUser = $conversation->otherUser($user->id);
            if ($otherUser) {
                if ($user->hasBlocked($otherUser->id) || $otherUser->hasBlocked($user->id)) {
                    return false;
                }
            }
        }

        return true;
    }
}
