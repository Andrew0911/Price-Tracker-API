<?php

namespace App\Filters;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class ProductFilter extends BaseFilter
{
    public function added_after(string $date): Builder
    {
        return $this->query->where('product_user.created_at', '>=',  Carbon::parse($date));
    }
}
