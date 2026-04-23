<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class ApiBaseRequest extends FormRequest
{
    use ApiResponseTrait;

    protected function failedValidation(Validator $validator)
    {
        $errors = new ValidationException($validator)->errors();

        $firstKey = array_key_first($errors);

        throw new HttpResponseException(
            $this->sendFailResponse(
                $errors[$firstKey][0],
                $errors,
                status: Response::HTTP_UNPROCESSABLE_ENTITY
            )
        );
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            $this->sendFailResponse(__('general.not_allowed'), status: Response::HTTP_UNAUTHORIZED)
        );
    }
}