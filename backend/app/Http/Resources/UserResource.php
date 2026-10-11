<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isSelf = ($request->user() && $request->user()->id === $this->id)
            || $request->is('*auth/register')
            || $request->is('*auth/login');

        $isOnline = false;
        if ($this->last_seen_at) {
            $isOnline = $this->last_seen_at->gt(now()->subMinutes(2));
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->when($isSelf, $this->email),
            'avatar_url' => $this->avatar_url,
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'is_online' => $isOnline,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
