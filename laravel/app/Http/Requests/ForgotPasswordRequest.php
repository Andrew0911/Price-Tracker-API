<?php

namespace App\Http\Requests;

class ForgotPasswordRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
