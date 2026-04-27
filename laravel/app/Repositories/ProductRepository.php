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
        return auth()->user()
            ->products()
            ->when($filter, fn($query) => $filter->apply($query))
            ->latest('products.created_at')
            ->paginate(request()->integer('perPage', 10))
            ->through(fn($p) => $p->makeHidden(['created_at', 'updated_at', 'pivot']));
    }
}