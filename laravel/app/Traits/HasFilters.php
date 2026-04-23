<?php

namespace App\Traits;

use App\Filters\BaseFilter;
use Illuminate\Database\Eloquent\Builder;

trait HasFilters
{
    public function scopeFilter($query, BaseFilter $filter): Builder
    {
        return $filter->apply($query);
    }
}