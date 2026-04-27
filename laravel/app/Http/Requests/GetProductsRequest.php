<?php

namespace App\Http\Requests;

class GetProductsRequest extends ApiBaseRequest
{
        /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => [
                'nullable',
                'integer',
                'min:1'
            ],
            'perPage' => [
                'nullable',
                'integer',
                'min:1',
                'max:100'
            ],
            'added_after' => [
                'nullable',
                'date',
            ]
        ];
    }
}
