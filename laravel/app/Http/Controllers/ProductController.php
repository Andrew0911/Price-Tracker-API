<?php

namespace App\Http\Controllers;

use App\Enums\StoreEnum;
use App\Filters\ProductFilter;
use App\Http\Controllers\BaseController;
use App\Http\Requests\GetProductsRequest;
use App\Http\Requests\StoreProductRequest;
use App\Models\PriceAlert;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\ScraperService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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

        $domain = strtolower(parse_url($request->url, PHP_URL_HOST));
        $proxiedDomains = StoreEnum::requiredProxyDomains();

        if(!$user->is_admin && in_array($domain, $proxiedDomains)){
            return $this->sendFailResponse('You do not have permission to add a product from this store');
        }

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
        } catch (QueryException|\PDOException $e) {
            report($e);

            return $this->sendErrorResponse();
        } catch (RuntimeException $e) {
            return $this->sendFailResponse($e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return $this->sendErrorResponse();
        }

        return $this->sendSuccessResponse(data: $product);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();
        $isInWishlist = $user->products()->where('products.id', $product->id)->exists();

        if (!$user->is_admin && !$isInWishlist) {
            return $this->sendErrorResponse(status: Response::HTTP_FORBIDDEN);
        }

        $product->load([
            'priceHistory' => fn($q) => $q->orderBy('checked_at')->limit(60),
            'priceAlerts' => fn($q) => $q
                ->where('user_id', $user->id)
                ->where(function ($q) {
                    $q->whereNull('triggered_at')
                        ->orWhere('type', PriceAlert::TYPE_PRICE_DROP);
                }),
        ]);
        $product->setAttribute('is_in_wishlist', $isInWishlist);

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
