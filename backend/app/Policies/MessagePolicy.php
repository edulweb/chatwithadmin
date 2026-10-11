<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    /**
     * Determine whether the user can view the message.
     */
    public function view(User $user, Message $message): bool
    {
        return $message->conversation->members()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can update the message.
     */
    public function update(User $user, Message $message): bool
    {
        return (int) $message->sender_id === (int) $user->id && is_null($message->deleted_at);
    }

    /**
     * Determine whether the user can delete the message.
     */
    public function delete(User $user, Message $message): bool
    {
        return (int) $message->sender_id === (int) $user->id;
    }
}
