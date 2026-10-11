<?php

use App\Http\Controllers\Api\V1\AttachmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Broadcasting authentication route for Sanctum SPA
Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::prefix('v1')->group(function () {
    // Public authentication routes with rate limiting
    Route::prefix('auth')->middleware('throttle:15,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // Dynamic broadcasting configuration (driver, keys, endpoints without secrets)
    Route::get('broadcasting/config', function () {
        $driver = config('broadcasting.default', env('BROADCAST_CONNECTION', 'reverb'));
        return response()->json([
            'driver' => $driver,
            'reverb' => [
                'key' => config('broadcasting.connections.reverb.key'),
                'host' => config('broadcasting.connections.reverb.options.host') ?: 'localhost',
                'port' => (int) (config('broadcasting.connections.reverb.options.port') ?: 8080),
                'scheme' => config('broadcasting.connections.reverb.options.scheme') ?: 'http',
            ],
            'pusher' => [
                'key' => config('broadcasting.connections.pusher.key'),
                'cluster' => config('broadcasting.connections.pusher.options.cluster') ?: 'mt1',
                'host' => env('PUSHER_HOST') ?: null,
                'port' => (int) (config('broadcasting.connections.pusher.options.port') ?: 443),
                'scheme' => config('broadcasting.connections.pusher.options.scheme') ?: 'https',
            ],
        ]);
    });

    // Attachment download (handles Bearer token header or ?token= query for <img> tags)
    Route::get('attachments/{attachment}/download', [AttachmentController::class, 'download']);

    // Authenticated API routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth & Profile
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::patch('me', [AuthController::class, 'updateProfile']);
        Route::post('me/password', [AuthController::class, 'changePassword']);

        // Users & Blocking
        Route::get('users', [UserController::class, 'index']);
        Route::post('users/{id}/block', [UserController::class, 'block']);
        Route::delete('users/{id}/block', [UserController::class, 'unblock']);
        Route::get('blocked-users', [UserController::class, 'blockedUsers']);

        // Conversations
        Route::get('conversations', [ConversationController::class, 'index']);
        Route::post('conversations', [ConversationController::class, 'store']);
        Route::get('conversations/{conversation}', [ConversationController::class, 'show']);
        Route::get('conversations/{conversation}/messages', [ConversationController::class, 'messages']);
        Route::post('conversations/{conversation}/messages', [ConversationController::class, 'sendMessage']);
        Route::post('conversations/{conversation}/read', [ConversationController::class, 'markAsRead']);
        Route::post('conversations/{conversation}/typing', [ConversationController::class, 'typing']);

        // Messages
        Route::patch('messages/{message}', [MessageController::class, 'update']);
        Route::delete('messages/{message}', [MessageController::class, 'destroy']);
    });
});
