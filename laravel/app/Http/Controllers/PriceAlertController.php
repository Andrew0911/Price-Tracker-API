<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StorePriceAlertRequest;
use App\Models\Product;
use App\Models\PriceAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PriceAlertController extends BaseController
{
    public function store(StorePriceAlertRequest $request, Product $product): JsonResponse
    {
        if (!$request->user()->products()->where('products.id', $product->id)->exists()) {
            return $this->sendErrorResponse(status: Response::HTTP_FORBIDDEN);
        }

        if ($product->scrape_error) {
            return $this->sendErrorResponse(
                message: 'Cannot set a price alert on an unavailable product.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ((float) $request->target_price >= (float) $product->current_price) {
            return $this->sendErrorResponse(
                message: 'The target price must be lower than the current product price.',
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $alert = $product->priceAlerts()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['target_price' => $request->target_price, 'notified_at' => null, 'triggered_at' => null],
        );

        return $this->sendSuccessResponse(data: $alert);
    }

    public function destroy(Request $request, Product $product, PriceAlert $alert): JsonResponse
    {
        if (!$request->user()->products()->where('products.id', $product->id)->exists()) {
            return $this->sendErrorResponse(status: Response::HTTP_FORBIDDEN);
        }

        if ($alert->product_id !== $product->id || $alert->user_id !== $request->user()->id) {
            return $this->sendErrorResponse(status: Response::HTTP_NOT_FOUND);
        }

        $alert->delete();

        return $this->sendSuccessResponse();
    }
}
