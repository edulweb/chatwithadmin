<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'sha256',
        'scan_status',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function isImage(): bool
    {
        if (str_starts_with($this->mime_type ?? '', 'image/')) {
            return true;
        }

        $extension = strtolower(pathinfo($this->original_name ?? '', PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'bmp', 'ico', 'avif']);
    }

    public function isAudio(): bool
    {
        $extension = strtolower(pathinfo($this->original_name ?? '', PATHINFO_EXTENSION));
        if (in_array($extension, ['mp3', 'wav', 'ogg', 'm4a', 'aac', 'flac', 'opus', 'wma', 'weba', 'mid', 'midi'])) {
            return true;
        }

        return str_starts_with($this->mime_type ?? '', 'audio/');
    }

    public function isVideo(): bool
    {
        if ($this->isAudio()) {
            return false;
        }

        $extension = strtolower(pathinfo($this->original_name ?? '', PATHINFO_EXTENSION));
        if (in_array($extension, ['mp4', 'webm', 'ogv', 'mov', 'mkv', 'avi', 'wmv', 'flv', 'm4v', '3gp'])) {
            return true;
        }

        return str_starts_with($this->mime_type ?? '', 'video/');
    }

    public function getResolvedMimeType(): string
    {
        if (!empty($this->mime_type) && $this->mime_type !== 'application/octet-stream') {
            return $this->mime_type;
        }

        $extension = strtolower(pathinfo($this->original_name ?? '', PATHINFO_EXTENSION));
        $map = [
            // Video
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'ogv' => 'video/ogg',
            'mov' => 'video/quicktime',
            'm4v' => 'video/mp4',
            'mkv' => 'video/x-matroska',
            'avi' => 'video/x-msvideo',
            'wmv' => 'video/x-ms-wmv',
            'flv' => 'video/x-flv',
            '3gp' => 'video/3gpp',
            // Audio
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'm4a' => 'audio/mp4',
            'aac' => 'audio/aac',
            'flac' => 'audio/flac',
            'oga' => 'audio/ogg',
            'opus' => 'audio/opus',
            'weba' => 'audio/webm',
            'wma' => 'audio/x-ms-wma',
            'mid' => 'audio/midi',
            'midi' => 'audio/midi',
            // Images
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'bmp' => 'image/bmp',
            'ico' => 'image/x-icon',
            'avif' => 'image/avif',
            // Documents
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'json' => 'application/json',
            'html' => 'text/html',
        ];

        return $map[$extension] ?? ($this->mime_type ?: 'application/octet-stream');
    }
}
