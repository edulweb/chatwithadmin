<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isDisabled()) {
            // Revoke current token if using Sanctum
            $token = $request->user()->currentAccessToken();
            if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
                $token->delete();
            }

            return response()->json([
                'message' => 'Your account has been disabled by an administrator. Please contact support.',
                'account_disabled' => true,
            ], 403);
        }

        if ($user && (!$user->isActive() || $user->email_verified_at === null)) {
            $token = $request->user()->currentAccessToken();
            if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
                $token->delete();
            }

            return response()->json([
                'message' => 'Your account has not been activated or verified yet.',
                'account_unactivated' => true,
            ], 403);
        }

        return $next($request);
    }
}
