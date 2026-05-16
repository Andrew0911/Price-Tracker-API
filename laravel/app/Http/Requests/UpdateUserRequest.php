<?php

namespace App\Http\Requests;

class UpdateUserRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'private_account' => [
                'required',
                'boolean',
            ],
        ];
    }
}
