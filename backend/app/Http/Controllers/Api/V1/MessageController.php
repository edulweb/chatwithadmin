<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\MessageDeleted;
use App\Events\MessageUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    public function update(UpdateMessageRequest $request, Message $message): JsonResponse
    {
        Gate::authorize('update', $message);

        $message->update([
            'body' => $request->body,
            'edited_at' => now(),
        ]);

        $message->load(['sender', 'attachments', 'replyTo.sender', 'reads']);

        broadcast(new MessageUpdated($message))->toOthers();

        return response()->json([
            'data' => new MessageResource($message),
            'message' => 'Message updated successfully',
        ]);
    }

    public function destroy(Request $request, Message $message): JsonResponse
    {
        Gate::authorize('delete', $message);

        $conversationId = $message->conversation_id;
        $messageId = $message->id;

        // Soft delete message
        $message->delete();

        broadcast(new MessageDeleted($conversationId, $messageId))->toOthers();

        return response()->json([
            'message' => 'Message deleted successfully',
        ]);
    }
}
