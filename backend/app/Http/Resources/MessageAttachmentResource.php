<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageAttachmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'message_id' => $this->message_id,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'resolved_mime_type' => $this->getResolvedMimeType(),
            'size_bytes' => $this->size_bytes,
            'is_image' => $this->isImage(),
            'is_video' => $this->isVideo(),
            'is_audio' => $this->isAudio(),
            'scan_status' => $this->scan_status,
            'download_url' => url("/api/v1/attachments/{$this->id}/download"),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
