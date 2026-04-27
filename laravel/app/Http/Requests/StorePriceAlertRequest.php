<?php

namespace App\Http\Requests;

class StorePriceAlertRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_price' => [
                'required',
                'numeric',
                'min:0',
                'max:100000'
            ],
        ];
    }
}
