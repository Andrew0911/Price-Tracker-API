<?php

namespace App\Http\Requests;

class SendMessageRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => [
                'nullable',
                'string',
                'max:2000'
            ],
            'product_id' => [
                'nullable',
                'integer',
                'exists:products,id'
            ],
        ];
    }
}
