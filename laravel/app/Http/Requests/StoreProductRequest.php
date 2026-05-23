<?php

namespace App\Http\Requests;

class StoreProductRequest extends ApiBaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->filled('url')) {
            return;
        }

        $this->merge([
            'url' => str_replace('|', '%7C', $this->input('url')),
        ]);
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
