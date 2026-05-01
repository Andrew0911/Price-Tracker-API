<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\DeleteAccountRequest;
use App\Mail\RegisterEmail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Mail;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends BaseController
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $verificationToken = Str::random(64);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verification_token' => $verificationToken,
            'email_verification_token_expires_at' => now()->addHours(24),
        ]);

        $verificationUrl = config('app.url') . '/api/verify-email?token=' . $verificationToken . '&email=' . urlencode($user->email);

        Mail::to($request->email)
            ->locale(app()->getLocale())
            ->send(new RegisterEmail(array_merge($request->all(), ['verification_url' => $verificationUrl]))->onQueue('emails'));

        return $this->sendSuccessResponse(__('general.registrationSuccessful'));
    }

    public function verifyEmail(Request $request): RedirectResponse
    {
        $token = $request->query('token');
        $email = $request->query('email');
        $loginUrl = env('FRONTEND_URL') . '/login';

        if (!$token || !$email) {
            return redirect($loginUrl . '?verified=0&error=invalid_token');
        }

        $user = User::where('email', $email)
            ->where('email_verification_token', $token)
            ->first();

        if (!$user) {
            return redirect($loginUrl . '?verified=0&error=invalid_token');
        }

        if ($user->email_verification_token_expires_at->isPast()) {
            return redirect($loginUrl . '?verified=0&error=token_expired');
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->email_verification_token_expires_at = null;
        $user->save();

        return redirect($loginUrl . '?verified=1');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->sendFailResponse(__('general.invalidCredentials'), status: Response::HTTP_UNAUTHORIZED);
        }

        if (empty($user->email_verified_at)) {
            return $this->sendFailResponse(__('general.emailNotVerified'), status: Response::HTTP_UNAUTHORIZED);
        }

        $expiresAt = now()->addMinutes(config('sanctum.expiration'));

        // invalidate previous tokens
        $user->tokens()->delete();

        $token = $user->createToken('user-login')->plainTextToken;

        return $this->sendSuccessResponse(__('general.loginSuccessful'), [
            'token' => $token,
            'tokenType' => 'Bearer',
            'expiresAt' => $expiresAt->toIso8601String()
        ]);
    }

    public function logout(): JsonResponse
    {
        auth('sanctum')->user()->currentAccessToken()->delete();

        return $this->sendSuccessResponse(__('general.logoutSuccessful'));
    }

    public function user(): JsonResponse
    {
       return $this->sendSuccessResponse(data: auth('sanctum')->user());
    }

    public function deleteAccount(DeleteAccountRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return $this->sendErrorResponse(
                message: __('general.invalidCredentials'),
                errors: ['password' => [__('general.invalidCredentials')]],
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $user->tokens()->delete();
        $user->delete();

        return $this->sendSuccessResponse('Account deleted successfully.');
    }
}