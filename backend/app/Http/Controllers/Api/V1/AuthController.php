<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Public registration is disabled. Only administrators can register users.',
        ], 403);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $throttleKey = 'login|' . $request->ip() . '|' . strtolower($request->login);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'login' => ["Too many login attempts. Please try again in {$seconds} seconds."],
            ])->status(429);
        }

        $login = strtolower($request->login);
        $user = User::where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'login' => ['These credentials do not match our records.'],
            ]);
        }

        if ($user->isDisabled()) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'login' => ['Your account has been disabled by an administrator. Please contact support.'],
            ])->status(403);
        }

        if (!$user->isActive() || $user->email_verified_at === null) {
            RateLimiter::hit($throttleKey, 60);
            throw ValidationException::withMessages([
                'login' => ['Your account is not activated. Please verify your email using the activation link sent to your inbox.'],
            ])->status(403);
        }

        RateLimiter::clear($throttleKey);

        $user->update(['last_seen_at' => now()]);
        $token = $user->createToken('connectly_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()->delete();
            $user->update(['last_seen_at' => now()]);
        }

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->update(['last_seen_at' => now()]);

        return response()->json([
            'data' => new UserResource($user),
            'message' => 'User profile retrieved',
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $emailChanged = false;

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('username')) {
            $user->username = strtolower($request->username);
        }

        if ($request->has('email')) {
            $newEmail = strtolower(trim($request->email));
            if ($newEmail !== $user->email) {
                // If user updates email, require verification link sent to new email
                $token = \Illuminate\Support\Str::random(40);
                $user->pending_email = $newEmail;
                $user->activation_token = $token;
                $emailChanged = true;

                try {
                    $user->notify(new \App\Notifications\VerifyNewEmailNotification($token, $newEmail));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send email verification: ' . $e->getMessage());
                }
            }
        }

        if ($request->hasFile('avatar')) {
            // Delete old avatar if custom
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar_path = $path;
        }

        $user->save();

        $message = $emailChanged
            ? 'Profile updated. A verification link has been sent to your new email (' . $user->pending_email . '). Your email will update once verified.'
            : 'Profile updated successfully';

        return response()->json([
            'message' => $message,
            'data' => new UserResource($user),
            'email_verification_pending' => $emailChanged,
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Password changed successfully',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if ($user) {
            // Invalidate existing tokens for this email
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $email)->delete();

            $token = \Illuminate\Support\Str::random(60);
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')->insert([
                'email' => $email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]);

            try {
                $user->notify(new \App\Notifications\ResetPasswordNotification($token, $email));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send reset password email: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'If an account exists with that email address, a password reset link has been sent.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = strtolower(trim($request->email));
        $record = \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record || !Hash::check($request->token, $record->token)) {
            throw ValidationException::withMessages([
                'token' => ['This password reset token is invalid or has expired.'],
            ]);
        }

        // Token expires after 60 minutes
        if (\Carbon\Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw ValidationException::withMessages([
                'token' => ['This password reset token has expired. Please request a new one.'],
            ]);
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['We could not find a user with that email address.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Revoke all tokens on password reset for security
        $user->tokens()->delete();
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', $email)->delete();

        return response()->json([
            'message' => 'Your password has been reset successfully. You can now log in with your new password.',
        ]);
    }

    public function activateAccount(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $user = User::where('activation_token', $request->token)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid or expired activation link.',
            ], 400);
        }

        $user->update([
            'is_active' => true,
            'email_verified_at' => now(),
            'activation_token' => null,
        ]);

        return response()->json([
            'message' => 'Account successfully activated! You can now log in.',
            'data' => new UserResource($user),
        ]);
    }

    public function verifyEmailChange(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $user = User::where('activation_token', $request->token)
            ->whereNotNull('pending_email')
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid or expired email verification link.',
            ], 400);
        }

        $newEmail = $user->pending_email;

        // Ensure email isn't taken in the meantime
        if (User::where('email', $newEmail)->where('id', '!=', $user->id)->exists()) {
            return response()->json([
                'message' => 'This email address is already in use by another account.',
            ], 422);
        }

        $user->update([
            'email' => $newEmail,
            'pending_email' => null,
            'activation_token' => null,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Your email address has been successfully verified and updated!',
            'data' => new UserResource($user),
        ]);
    }

    public function resendActivation(Request $request): JsonResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
        ]);

        $login = strtolower(trim($request->login));
        $user = User::where('email', $login)->orWhere('username', $login)->first();

        if ($user && (!$user->isActive() || $user->email_verified_at === null)) {
            $token = \Illuminate\Support\Str::random(40);
            $user->update(['activation_token' => $token]);

            try {
                $user->notify(new \App\Notifications\AccountActivationNotification($token));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed to resend activation: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'If an unactivated account exists, an activation email has been resent.',
        ]);
    }
}
