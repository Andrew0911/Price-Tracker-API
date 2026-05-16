<?php

namespace App\Http\Requests;

class ValidateResetPasswordTokenRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
        ];
    }
}
