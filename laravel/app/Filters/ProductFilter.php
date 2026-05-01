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

    public function store(string $store): Builder
    {
        return $this->query->where('products.store', $store);
    }

    public function status(int $status): Builder
    {
        return match ($status) {
            1 => $this->query->whereNull('products.scrape_error'),
            0 => $this->query->whereNotNull('products.scrape_error'),
            default => $this->query,
        };
    }
}
