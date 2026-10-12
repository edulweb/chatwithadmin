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
        $isSelfOrAdmin = ($request->user() && ($request->user()->id === $this->id || $request->user()->isAdmin()))
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
            'email' => $this->when($isSelfOrAdmin, $this->email),
            'avatar_url' => $this->avatar_url,
            'is_admin' => (bool) $this->is_admin,
            'is_disabled' => (bool) $this->is_disabled,
            'is_active' => (bool) $this->is_active,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'is_verified' => $this->email_verified_at !== null,
            'pending_email' => $this->when($isSelfOrAdmin, $this->pending_email),
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'is_online' => $isOnline,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
