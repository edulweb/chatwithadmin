<?php

namespace App\Policies;

use App\Models\MessageAttachment;
use App\Models\User;

class MessageAttachmentPolicy
{
    /**
     * Determine whether the user can view the attachment.
     */
    public function view(User $user, MessageAttachment $attachment): bool
    {
        return $attachment->message->conversation->members()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can download the attachment.
     */
    public function download(User $user, MessageAttachment $attachment): bool
    {
        return $this->view($user, $attachment);
    }

    /**
     * Determine whether the user can delete the attachment.
     */
    public function delete(User $user, MessageAttachment $attachment): bool
    {
        return (int) $attachment->message->sender_id === (int) $user->id;
    }
}
