<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCreateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->input('search', ''));
        $currentUser = $request->user();

        $query = User::where('id', '!=', $currentUser->id);

        // Regular users can only see and chat with administrators
        if (!$currentUser->isAdmin()) {
            $query->where('is_admin', true);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->limit(20)->get();

        return response()->json([
            'data' => UserResource::collection($users),
            'message' => 'Users retrieved successfully',
        ]);
    }

    public function store(AdminCreateUserRequest $request): JsonResponse
    {
        $activationToken = \Illuminate\Support\Str::random(40);
        $requireActivation = (bool) $request->input('require_activation', true);

        $user = User::create([
            'name' => $request->name,
            'username' => strtolower($request->username),
            'email' => strtolower($request->email),
            'password' => Hash::make($request->password),
            'is_admin' => (bool) $request->input('is_admin', false),
            'is_active' => !$requireActivation,
            'email_verified_at' => $requireActivation ? null : now(),
            'activation_token' => $requireActivation ? $activationToken : null,
            'last_seen_at' => null,
        ]);

        if ($requireActivation) {
            try {
                $user->notify(new \App\Notifications\AccountActivationNotification($activationToken));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send activation email: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => $requireActivation
                ? 'User registered successfully. An account activation email has been sent.'
                : 'User registered successfully by administrator.',
            'data' => new UserResource($user),
            'activation_token' => $requireActivation ? $activationToken : null,
        ], 201);
    }

    public function block(Request $request, int $id): JsonResponse
    {
        $currentUser = $request->user();
        $currentUserId = $currentUser->id;

        if ($currentUserId === $id) {
            return response()->json([
                'message' => 'You cannot block yourself.',
            ], 422);
        }

        $targetUser = User::findOrFail($id);

        if ($targetUser->isAdmin()) {
            return response()->json([
                'message' => 'Administrators cannot be blocked.',
            ], 403);
        }

        UserBlock::firstOrCreate([
            'user_id' => $currentUserId,
            'blocked_user_id' => $targetUser->id,
        ]);

        return response()->json([
            'message' => 'User blocked successfully',
        ]);
    }

    public function disable(Request $request, int $id): JsonResponse
    {
        $currentUser = $request->user();

        if (!$currentUser->isAdmin()) {
            return response()->json([
                'message' => 'Only administrators can disable user accounts.',
            ], 403);
        }

        if ($currentUser->id === $id) {
            return response()->json([
                'message' => 'You cannot disable your own administrator account.',
            ], 422);
        }

        $targetUser = User::findOrFail($id);
        $targetUser->update(['is_disabled' => true]);

        // Revoke all personal access tokens for target user
        $targetUser->tokens()->delete();

        // Clear active sessions for target user if sessions table is used
        try {
            DB::table('sessions')->where('user_id', $targetUser->id)->delete();
        } catch (\Throwable $e) {
            // ignore if sessions table not configured
        }

        // Broadcast UserDisabled event to target user's private channel immediately
        broadcast(new \App\Events\UserDisabled($targetUser->id))->toOthers();

        return response()->json([
            'message' => "Account for {$targetUser->name} has been disabled and logged out.",
            'data' => new UserResource($targetUser->fresh()),
        ]);
    }

    public function enable(Request $request, int $id): JsonResponse
    {
        $currentUser = $request->user();

        if (!$currentUser->isAdmin()) {
            return response()->json([
                'message' => 'Only administrators can enable user accounts.',
            ], 403);
        }

        $targetUser = User::findOrFail($id);
        $targetUser->update(['is_disabled' => false]);

        return response()->json([
            'message' => "Account for {$targetUser->name} has been enabled.",
            'data' => new UserResource($targetUser->fresh()),
        ]);
    }

    public function unblock(Request $request, int $id): JsonResponse
    {
        $currentUserId = $request->user()->id;

        UserBlock::where('user_id', $currentUserId)
            ->where('blocked_user_id', $id)
            ->delete();

        return response()->json([
            'message' => 'User unblocked successfully',
        ]);
    }

    public function blockedUsers(Request $request): JsonResponse
    {
        $user = $request->user();
        $blockedUsers = User::whereIn('id', function ($query) use ($user) {
            $query->select('blocked_user_id')
                ->from('user_blocks')
                ->where('user_id', $user->id);
        })->get();

        return response()->json([
            'data' => UserResource::collection($blockedUsers),
            'message' => 'Blocked users retrieved successfully',
        ]);
    }
}
