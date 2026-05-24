<?php

namespace App\Repositories;

use App\Filters\BaseFilter;
use App\Models\PriceAlert;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function paginate(?BaseFilter $filter = null): LengthAwarePaginator
    {
        $userId = auth()->id();

        return auth()->user()
            ->products()
            ->with([
                'priceAlerts' => fn($q) => $q
                    ->where('user_id', $userId)
                    ->where(function ($q) {
                        $q->whereNull('triggered_at')
                            ->orWhere('type', PriceAlert::TYPE_PRICE_DROP);
                    }),
            ])
            ->when($filter, fn($query) => $filter->apply($query))
            ->latest('products.created_at')
            ->paginate(request()->integer('perPage', 10))
            ->through(function ($p) {
                return $p->makeHidden(['created_at', 'updated_at', 'pivot']);
            });
    }
}
