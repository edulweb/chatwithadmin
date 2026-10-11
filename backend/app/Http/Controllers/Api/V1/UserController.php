<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim($request->input('search', ''));
        $currentUserId = $request->user()->id;

        $query = User::where('id', '!=', $currentUserId);

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

    public function block(Request $request, int $id): JsonResponse
    {
        $currentUserId = $request->user()->id;

        if ($currentUserId === $id) {
            return response()->json([
                'message' => 'You cannot block yourself.',
            ], 422);
        }

        $targetUser = User::findOrFail($id);

        UserBlock::firstOrCreate([
            'user_id' => $currentUserId,
            'blocked_user_id' => $targetUser->id,
        ]);

        return response()->json([
            'message' => 'User blocked successfully',
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
