<?php

namespace App\Http\Controllers;

use App\Filters\ProductFilter;
use App\Http\Controllers\BaseController;
use App\Http\Requests\GetProductsRequest;
use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\ScraperService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends BaseController
{
    public function __construct(
        private ScraperService $scraper,
        private ProductRepository $repository
    ) {}

    public function index(GetProductsRequest $request, ProductFilter $filter): JsonResponse
    {
        $products = $this->repository->paginate($filter);
        return $this->sendSuccessResponse(data: $products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $user = $request->user();

        $alreadyTracked = $user->products()->where('url', $request->url)->exists();
        if ($alreadyTracked) {
            return $this->sendFailResponse('Product with this URL is already tracked');
        }

        try {
            $product = Product::where('url', $request->url)->first();

            if (!$product) {
                $result = $this->scraper->scrape($request->url);
                $now = now();

                $product = Product::create([
                    'url' => $request->url,
                    'name' => $result['name'],
                    'store' => $result['store'] ?? null,
                    'image_url' => $result['image_url'] ?? null,
                    'current_price' => $result['price'],
                    'currency' => $result['currency'],
                    'last_checked_at' => $now,
                ]);

                $product->priceHistory()->create([
                    'price' => $result['price'],
                    'currency' => $result['currency'],
                    'checked_at' => $now,
                ]);
            }

            $user->products()->attach($product->id);
        } catch (Exception $e) {
            return $this->sendFailResponse($e->getMessage());
        }

        return $this->sendSuccessResponse(data: $product);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        if (!$request->user()->products()->where('products.id', $product->id)->exists()) {
            return $this->sendErrorResponse(status: Response::HTTP_FORBIDDEN);
        }

        $product->load([
            'priceHistory' => fn($q) => $q->orderBy('checked_at')->limit(60),
            'priceAlerts' => fn($q) => $q->whereNull('triggered_at')->where('user_id', $request->user()->id),
        ]);

        return $this->sendSuccessResponse(data: $product);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        if (!$request->user()->products()->where('products.id', $product->id)->exists()) {
            return $this->sendErrorResponse(status: Response::HTTP_FORBIDDEN);
        }

        $request->user()->products()->detach($product->id);

        $product->priceAlerts()->where('user_id', $request->user()->id)->delete();

        return $this->sendSuccessResponse();
    }
}

