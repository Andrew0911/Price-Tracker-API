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

            $response['data'] = $data;

            if (is_object($data) && $data->resource instanceof LengthAwarePaginator) {
                $paginator = $data->resource;
                $response['page'] = $paginator->currentPage();
                $response['lastPage'] = $paginator->lastPage();
                $response['perPage'] = $paginator->perPage();
                $response['total'] = $paginator->total();
            }
        }

        if (!empty($extraData)) {
            $response = array_merge($response, $extraData);
        }

        return response()->json($response, $status, $extraHeaders);
    }
}