<?php

namespace App\Http\Controllers;

use App\Filters\AdminProductFilter;
use App\Http\Requests\GetProductsRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminProductsController extends BaseController
{
    public function index(GetProductsRequest $request, AdminProductFilter $filter): JsonResponse
    {
        if (!$request->user()->is_admin) {
            return $this->sendErrorResponse(status: Response::HTTP_FORBIDDEN);
        }

        $products = Product::query()
            ->withCount(['users', 'priceAlerts'])
            ->tap(fn ($query) => $filter->apply($query))
            ->latest('created_at')
            ->paginate($request->integer('perPage', 12))
            ->through(fn (Product $product) => $product->makeHidden(['updated_at']));

        return $this->sendSuccessResponse(data: $products);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        if (!$request->user()->is_admin) {
            return $this->sendErrorResponse(status: Response::HTTP_FORBIDDEN);
        }

        $product->delete();

        return $this->sendSuccessResponse('Product permanently deleted.');
    }
}
