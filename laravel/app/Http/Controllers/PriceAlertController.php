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

        $alert = $product->priceAlerts()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['target_price' => $request->target_price, 'is_active' => true],
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
