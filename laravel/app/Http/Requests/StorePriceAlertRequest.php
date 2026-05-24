<?php

namespace App\Http\Requests;

use App\Models\PriceAlert;
use Illuminate\Validation\Rule;

class StorePriceAlertRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'sometimes',
                'string',
                Rule::in([PriceAlert::TYPE_TARGET_PRICE, PriceAlert::TYPE_PRICE_DROP]),
            ],
            'target_price' => [
                'required_unless:type,' . PriceAlert::TYPE_PRICE_DROP,
                'nullable',
                'numeric',
                'min:0',
                'max:100000'
            ],
        ];
    }
}
