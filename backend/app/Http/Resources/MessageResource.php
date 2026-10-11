<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isDeleted = !is_null($this->deleted_at);
        $user = $request->user();

        // Read status: If sent by current user, is it read by other member(s)?
        $isRead = false;
        if ($this->relationLoaded('reads')) {
            $isRead = $this->reads->contains(function ($read) use ($user) {
                return $user ? $read->user_id !== $user->id : false;
            });
        } elseif ($this->relationLoaded('conversation') && $this->conversation && $user) {
            $otherMember = $this->conversation->conversationMembers
                ?->firstWhere('user_id', '!=', $this->sender_id);
            if ($otherMember && $otherMember->last_read_message_id >= $this->id) {
                $isRead = true;
            }
        }

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'type' => $this->type,
            'body' => $isDeleted ? 'This message was deleted' : $this->body,
            'reply_to_id' => $this->reply_to_id,
            'reply_to' => $this->when($this->reply_to_id && $this->relationLoaded('replyTo') && $this->replyTo, function () {
                $rt = $this->replyTo;
                return [
                    'id' => $rt->id,
                    'sender_id' => $rt->sender_id,
                    'sender_name' => $rt->sender?->name ?? 'User',
                    'body' => $rt->deleted_at ? 'This message was deleted' : $rt->body,
                    'type' => $rt->type,
                ];
            }),
            'attachments' => $isDeleted ? [] : MessageAttachmentResource::collection($this->whenLoaded('attachments')),
            'is_read' => $isRead,
            'edited_at' => $this->edited_at?->toISOString(),
            'is_deleted' => $isDeleted,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
