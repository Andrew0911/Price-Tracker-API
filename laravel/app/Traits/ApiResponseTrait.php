<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

trait ApiResponseTrait
{
    public function sendErrorResponse(
        ?string $message = null,
        ?array $errors = null,
        int $status = 500,
        array $extraHeaders = []
    ): JsonResponse {
        $response = [
            'message' => $message ?? __('general.error'),
        ];

        !is_null($errors) && $response['errors'] = $errors;

        return response()->json($response, $status, $extraHeaders);
    }

    public function sendFailResponse(
        string $message,
        ?array $errors = null,
        int $status = 400,
        array $extraHeaders = []
    ): JsonResponse {
        $response = [
            'message' => $message,
        ];

        !is_null($errors) && $response['errors'] = $errors;

        return response()->json($response, $status, $extraHeaders);
    }

    public function sendSuccessResponse(
        ?string $message = null,
        mixed $data = [],
        array $extraData = [],
        int $status = 200,
        array $extraHeaders = []
    ): JsonResponse {
        $response = [
            'message' => $message ?? __('general.success')
        ];

        if (!empty($data)) {
            if ($data instanceof LengthAwarePaginator) {
                $response['data'] = $data->items();
                $response['page'] = $data->currentPage();
                $response['lastPage'] = $data->lastPage();
                $response['perPage'] = $data->perPage();
                $response['total'] = $data->total();
            } else {
                $response['data'] = $data;
            }
        }

        if (!empty($extraData)) {
            $response = array_merge($response, $extraData);
        }

        return response()->json($response, $status, $extraHeaders);
    }
}