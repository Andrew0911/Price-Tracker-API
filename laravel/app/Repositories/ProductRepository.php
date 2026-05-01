<?php

namespace App\Repositories;

use App\Filters\BaseFilter;
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
            ->with(['priceAlerts' => fn($q) => $q->whereNull('triggered_at')->where('user_id', $userId)])
            ->when($filter, fn($query) => $filter->apply($query))
            ->latest('products.created_at')
            ->paginate(request()->integer('perPage', 10))
            ->through(function ($p) {
                return $p->makeHidden(['created_at', 'updated_at', 'pivot']);
            });
    }
}