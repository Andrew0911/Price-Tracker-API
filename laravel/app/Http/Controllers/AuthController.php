<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\ValidateResetPasswordTokenRequest;
use App\Http\Requests\VerifyEmailRequest;
use App\Mail\RegisterEmail;
use App\Mail\ResetPasswordEmail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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

        $verificationUrl = rtrim(env('FRONTEND_URL'), '/') . '/verify-email?token='
            . urlencode($verificationToken)
            . '&email='
            . urlencode($user->email);

        Mail::to($request->email)
            ->locale(app()->getLocale())
            ->send(new RegisterEmail(array_merge($request->all(), ['verification_url' => $verificationUrl]))->onQueue('emails'));

        return $this->sendSuccessResponse(__('general.registrationSuccessful'));
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)
            ->where('email_verification_token', $request->token)
            ->first();

        if (!$user) {
            return $this->sendFailResponse(
                'This verification link is invalid or has already been used.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ($user->email_verification_token_expires_at->isPast()) {
            return $this->sendFailResponse(
                'This verification link has expired.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->email_verification_token_expires_at = null;
        $user->save();

        return $this->sendSuccessResponse('Email verified successfully.');
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

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if ($user) {
            $token = Password::broker()->createToken($user);
            $resetUrl = rtrim(env('FRONTEND_URL'), '/') . '/reset-password?token='
                . urlencode($token)
                . '&email='
                . urlencode($user->email);

            Mail::to($user->email)
                ->locale(app()->getLocale())
                ->send(new ResetPasswordEmail([
                    'reset_url' => $resetUrl,
                ])->onQueue('emails'));
        }

        return $this->sendSuccessResponse(
            'If an account exists for that email, a password reset link has been sent.'
        );
    }

    public function validateResetPasswordToken(ValidateResetPasswordTokenRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Password::broker()->tokenExists($user, $request->token)) {
            return $this->sendFailResponse(
                'This password reset link is invalid or has expired.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->sendSuccessResponse();
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->sendFailResponse(
                'This password reset link is invalid or has expired.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        return $this->sendSuccessResponse('Password reset successfully.');
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

    public function updateUser(UpdateUserRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->is_admin) {
            return $this->sendFailResponse(
                'Admin accounts must remain private.',
                status: Response::HTTP_FORBIDDEN,
            );
        }

        $user->update([
            'private_account' => $request->boolean('private_account'),
        ]);

        return $this->sendSuccessResponse(
            message: 'Account settings updated successfully.',
            data: $user->fresh(),
        );
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
