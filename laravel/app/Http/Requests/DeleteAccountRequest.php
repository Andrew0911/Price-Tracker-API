<?php

namespace App\Http\Requests;

class DeleteAccountRequest extends ApiBaseRequest
{
    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string'
            ],
        ];
    }
}
