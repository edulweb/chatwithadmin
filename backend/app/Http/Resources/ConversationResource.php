<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $otherUser = null;
        $isBlocked = false;
        $hasBlockedOther = false;

        if ($user && $this->relationLoaded('members')) {
            $other = $this->members->firstWhere('id', '!=', $user->id);
            if ($other) {
                $otherUser = new UserResource($other);
                $isBlocked = $user->isBlockedBy($other->id);
                $hasBlockedOther = $user->hasBlocked($other->id);
            }
        }

        // Calculate unread count and latest message for current user
        $unreadCount = 0;
        $clearedId = 0;
        if ($user) {
            $currentMember = $this->conversationMembers?->firstWhere('user_id', $user->id);
            $lastReadId = $currentMember?->last_read_message_id ?? 0;
            $clearedId = $currentMember?->cleared_message_id ?? 0;
            $cutoffId = max($lastReadId, $clearedId);

            if ($this->relationLoaded('messages')) {
                $unreadCount = $this->messages
                    ->where('sender_id', '!=', $user->id)
                    ->where('id', '>', $cutoffId)
                    ->whereNull('deleted_at')
                    ->count();
            } else {
                $unreadCount = $this->messages()
                    ->where('sender_id', '!=', $user->id)
                    ->where('id', '>', $cutoffId)
                    ->whereNull('deleted_at')
                    ->count();
            }
        }

        $latestMessage = null;
        if ($this->relationLoaded('latestMessage') && $this->latestMessage) {
            if (!$clearedId || $this->latestMessage->id > $clearedId) {
                $latestMessage = new MessageResource($this->latestMessage);
            }
        }

        return [
            'id' => $this->id,
            'type' => $this->type,
            'other_user' => $otherUser,
            'is_blocked' => $isBlocked,
            'has_blocked_other' => $hasBlockedOther,
            'latest_message' => $latestMessage,
            'unread_count' => $unreadCount,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
