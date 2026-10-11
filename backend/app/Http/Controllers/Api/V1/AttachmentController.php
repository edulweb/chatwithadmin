<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController extends Controller
{
    public function download(Request $request, MessageAttachment $attachment): Response
    {
        // Support token in query parameter for direct browser <img> and download links
        if (!$request->user() && $request->filled('token')) {
            $token = PersonalAccessToken::findToken($request->query('token'));
            if ($token && $token->tokenable) {
                auth()->setUser($token->tokenable);
            }
        }

        if (!$request->user()) {
            abort(401, 'Unauthenticated.');
        }

        Gate::authorize('download', $attachment);

        $disk = Storage::disk($attachment->disk);

        if (!$disk->exists($attachment->path)) {
            abort(404, 'Attachment file not found on disk.');
        }

        $fullPath = $disk->path($attachment->path);
        $isPreview = $request->boolean('preview') || $request->boolean('inline') || $request->boolean('stream');
        $mimeType = $attachment->getResolvedMimeType();

        if ($isPreview) {
            return response()->file($fullPath, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . addslashes($attachment->original_name) . '"',
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            ]);
        }

        return response()->download(
            $fullPath,
            $attachment->original_name,
            [
                'Content-Type' => $mimeType,
                'Cache-Control' => 'private, no-cache, no-store, must-revalidate',
            ]
        );
    }
}
