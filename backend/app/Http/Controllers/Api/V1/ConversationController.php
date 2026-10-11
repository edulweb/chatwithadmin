<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\MessageRead;
use App\Events\MessageSent;
use App\Events\UserTyping;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateConversationRequest;
use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\ConversationMember;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageRead as MessageReadModel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $conversations = Conversation::whereHas('conversationMembers', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->with([
            'members',
            'conversationMembers',
            'latestMessage.sender',
            'latestMessage.attachments',
        ])
        ->orderByDesc('updated_at')
        ->get();

        return response()->json([
            'data' => ConversationResource::collection($conversations),
            'message' => 'Conversations retrieved successfully',
        ]);
    }

    public function store(CreateConversationRequest $request): JsonResponse
    {
        $currentUserId = $request->user()->id;
        $targetUserId = (int) $request->user_id;

        // Verify target user exists
        $targetUser = User::findOrFail($targetUserId);

        // Check if either user has blocked the other
        if ($request->user()->hasBlocked($targetUserId) || $targetUser->hasBlocked($currentUserId)) {
            return response()->json([
                'message' => 'Cannot create conversation due to user block settings.',
            ], 403);
        }

        $canonicalPair = Conversation::makeCanonicalPair($currentUserId, $targetUserId);

        // Check if direct conversation already exists
        $conversation = Conversation::where('canonical_pair', $canonicalPair)->first();

        if ($conversation) {
            $conversation->load([
                'members',
                'conversationMembers',
                'latestMessage.sender',
                'latestMessage.attachments',
            ]);

            return response()->json([
                'data' => new ConversationResource($conversation),
                'message' => 'Conversation already exists',
            ]);
        }

        $conversation = DB::transaction(function () use ($currentUserId, $targetUserId, $canonicalPair) {
            $conv = Conversation::create([
                'type' => 'direct',
                'created_by' => $currentUserId,
                'canonical_pair' => $canonicalPair,
            ]);

            ConversationMember::create([
                'conversation_id' => $conv->id,
                'user_id' => $currentUserId,
                'joined_at' => now(),
            ]);

            ConversationMember::create([
                'conversation_id' => $conv->id,
                'user_id' => $targetUserId,
                'joined_at' => now(),
            ]);

            return $conv;
        });

        $conversation->load([
            'members',
            'conversationMembers',
            'latestMessage.sender',
            'latestMessage.attachments',
        ]);

        return response()->json([
            'data' => new ConversationResource($conversation),
            'message' => 'Conversation created successfully',
        ], 201);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $conversation->load([
            'members',
            'conversationMembers',
            'latestMessage.sender',
            'latestMessage.attachments',
        ]);

        return response()->json([
            'data' => new ConversationResource($conversation),
            'message' => 'Conversation details retrieved successfully',
        ]);
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $limit = min((int) $request->input('limit', 30), 100);
        $beforeId = $request->input('before_id');
        $afterId = $request->input('after_id');

        $query = $conversation->messages()
            ->withTrashed()
            ->with(['sender', 'attachments', 'replyTo.sender', 'reads']);

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        if ($afterId) {
            $query->where('id', '>', $afterId);
        }

        // Search within conversation if query is provided
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('body', 'like', "%{$search}%");
        }

        $messages = $query->orderBy('id', 'desc')->limit($limit + 1)->get();

        $hasMore = $messages->count() > $limit;
        if ($hasMore) {
            $messages = $messages->slice(0, $limit);
        }

        // Return messages sorted chronologically (oldest to newest)
        $sortedMessages = $messages->reverse()->values();

        return response()->json([
            'data' => MessageResource::collection($sortedMessages),
            'meta' => [
                'has_more' => $hasMore,
                'oldest_id' => $sortedMessages->first()?->id,
                'newest_id' => $sortedMessages->last()?->id,
            ],
            'message' => 'Messages retrieved successfully',
        ]);
    }

    public function sendMessage(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('sendMessage', $conversation);

        $user = $request->user();
        $hasFile = $request->hasFile('file');
        $body = $request->input('body');

        $type = 'text';
        if ($hasFile && !empty(trim($body ?? ''))) {
            $type = 'mixed';
        } elseif ($hasFile) {
            $type = 'file';
        }

        $message = DB::transaction(function () use ($request, $conversation, $user, $type, $hasFile, $body) {
            $msg = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'type' => $type,
                'body' => $body,
                'reply_to_id' => $request->input('reply_to_id'),
            ]);

            if ($hasFile) {
                $uploadedFile = $request->file('file');
                $hash = hash_file('sha256', $uploadedFile->getRealPath());
                $extension = $uploadedFile->getClientOriginalExtension();
                $randomName = Str::random(40) . ($extension ? '.' . $extension : '');
                $path = "conversations/{$conversation->id}/{$randomName}";

                // Store in private disk
                $uploadedFile->storeAs("conversations/{$conversation->id}", $randomName, 'chat_private');

                MessageAttachment::create([
                    'message_id' => $msg->id,
                    'disk' => 'chat_private',
                    'path' => $path,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'mime_type' => $uploadedFile->getMimeType() ?: 'application/octet-stream',
                    'size_bytes' => $uploadedFile->getSize(),
                    'sha256' => $hash,
                    'scan_status' => 'clean',
                ]);
            }

            // Update conversation timestamp
            $conversation->touch();

            // Sender automatically reads their own message
            ConversationMember::where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->update(['last_read_message_id' => $msg->id]);

            return $msg;
        });

        $message->load(['sender', 'attachments', 'replyTo.sender', 'reads']);

        // Broadcast event to private channel
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'data' => new MessageResource($message),
            'message' => 'Message sent successfully',
        ], 201);
    }

    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $userId = $request->user()->id;
        $latestMessage = $conversation->messages()->latest('id')->first();

        if (!$latestMessage) {
            return response()->json(['message' => 'No messages to mark as read']);
        }

        $currentMember = ConversationMember::where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->first();

        $previousLastRead = $currentMember?->last_read_message_id ?? 0;

        if ($latestMessage->id > $previousLastRead) {
            // Update last read message ID on member record
            ConversationMember::where('conversation_id', $conversation->id)
                ->where('user_id', $userId)
                ->update(['last_read_message_id' => $latestMessage->id]);

            // Add entries to message_reads for unread messages not sent by self
            $unreadMessages = $conversation->messages()
                ->where('id', '>', $previousLastRead)
                ->where('id', '<=', $latestMessage->id)
                ->where('sender_id', '!=', $userId)
                ->pluck('id');

            $now = now();
            $readRecords = [];
            foreach ($unreadMessages as $msgId) {
                $readRecords[] = [
                    'message_id' => $msgId,
                    'user_id' => $userId,
                    'read_at' => $now,
                ];
            }

            if (!empty($readRecords)) {
                MessageReadModel::upsert($readRecords, ['message_id', 'user_id'], ['read_at']);
            }

            // Broadcast read receipt
            broadcast(new MessageRead($conversation->id, $userId, $latestMessage->id))->toOthers();
        }

        return response()->json([
            'message' => 'Messages marked as read',
            'last_read_message_id' => $latestMessage->id,
        ]);
    }

    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        Gate::authorize('view', $conversation);

        $isTyping = $request->boolean('is_typing', true);

        // Ephemeral broadcast, zero DB row created
        broadcast(new UserTyping($conversation->id, $request->user(), $isTyping))->toOthers();

        return response()->json([
            'message' => 'Typing status broadcasted',
        ]);
    }
}
