<?php

namespace App\Http\Requests;

class StoreProductRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => [
                'required',
                'url',
                'max:2048',
            ],
        ];
    }
}
