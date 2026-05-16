<?php

namespace App\Http\Requests;

class ResetPasswordRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])\S+$/',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.regex' => __('general.passwordRequirements'),
            'password.confirmed' => __('general.passwordConfirmationMismatch'),
        ];
    }
}
