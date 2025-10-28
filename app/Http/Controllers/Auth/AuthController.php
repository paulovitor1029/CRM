<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\VerifyTwoFactorRequest;
use App\Models\FailedLogin;
use App\Models\User;
use App\Models\UserSecurity;
use App\Support\Totp;
use Illuminate\Contracts\Auth\StatefulGuard;
use App\Services\AuthorizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class AuthController
{
    public function __construct(
        private readonly StatefulGuard $guard,
        private readonly AuthorizationService $authz,
    ) {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $email = (string) $request->validated('email');
        $password = (string) $request->validated('password');
        $deviceId = (string) ($request->header('X-Device-Id') ?? $request->input('device_id', ''));

        $user = User::where('email', $email)->first();
        if (!$user || !Hash::check($password, $user->password)) {
            $this->logFailed($request, $email, 'invalid_credentials');
            return response()->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        // Load user security profile
        $sec = UserSecurity::firstOrCreate(['user_id' => $user->id]);

        if ($sec->two_factor_enabled) {
            // Start 2FA challenge flow; do not authenticate yet
            Session::put('2fa:pending_user_id', $user->id);
            Session::put('2fa:started_at', now()->toISOString());
            if ($deviceId !== '') {
                Session::put('device_id', $deviceId);
            }
            Log::info('2fa_challenge_started', [
                'email' => $email,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Two-factor authentication required',
            ], Response::HTTP_ACCEPTED);
        }

        // Authenticate and bind device to session
        $this->guard->login($user, false);
        Session::regenerate();
        if ($deviceId !== '') {
            Session::put('device_id', $deviceId);
        }
        $user->forceFill(['last_login_at' => now()])->save();
        $this->authz->primeSessionCache($user);
        Log::info('login_success', [
            'user_id' => $user->id,
            'email' => $email,
        ]);

        return response()->json([
            'message' => 'Authenticated',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], Response::HTTP_OK);
    }

    public function verify2fa(VerifyTwoFactorRequest $request): JsonResponse
    {
        $pendingUserId = Session::get('2fa:pending_user_id');
        if (!$pendingUserId) {
            return response()->json(['message' => 'No 2FA challenge'], Response::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = User::find($pendingUserId);
        if (!$user) {
            return response()->json(['message' => 'Invalid user'], Response::HTTP_BAD_REQUEST);
        }

        $sec = UserSecurity::firstOrCreate(['user_id' => $user->id]);
        if (!$sec->two_factor_enabled || !$sec->two_factor_secret) {
            return response()->json(['message' => '2FA not enabled'], Response::HTTP_BAD_REQUEST);
        }

        $code = (string) ($request->validated('code') ?? '');
        $recovery = (string) ($request->validated('recovery_code') ?? '');

        $verified = false;
        if ($code !== '') {
            $verified = Totp::verifyCode($sec->two_factor_secret, $code, 1);
        } elseif ($recovery !== '') {
            $verified = $sec->consumeRecoveryCode($recovery);
        }

        if (!$verified) {
            $sec->increment('two_factor_failed_attempts');
            Log::warning('2fa_verify_failed', [
                'user_id' => $user->id,
            ]);
            return response()->json(['message' => 'Invalid 2FA'], Response::HTTP_UNAUTHORIZED);
        }

        $sec->forceFill([
            'last_2fa_at' => now(),
            'two_factor_failed_attempts' => 0,
        ])->save();

        $this->guard->login($user, false);
        Session::regenerate();
        $this->authz->primeSessionCache($user);
        Log::info('login_success_2fa', [
            'user_id' => $user->id,
        ]);
        // Clear pending state
        Session::forget(['2fa:pending_user_id', '2fa:started_at']);

        return response()->json(['message' => 'Authenticated'], Response::HTTP_OK);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->guard->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $this->authz->clearSessionCache();
        Log::info('logout', [
            'user_id' => optional($user)->id,
        ]);
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->session()->regenerate();
        Log::info('session_refreshed', [
            'user_id' => optional($request->user())->id,
        ]);
        return response()->json(['message' => 'Session refreshed'], Response::HTTP_OK);
    }

    private function logFailed(Request $request, string $email, string $reason): void
    {
        FailedLogin::create([
            'email' => $email,
            'ip' => (string) $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'reason' => $reason,
        ]);
        Log::warning('login_failed', [
            'email' => $email,
            'reason' => $reason,
        ]);
    }
}
