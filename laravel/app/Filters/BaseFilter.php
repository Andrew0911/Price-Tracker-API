<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class BaseFilter
{
    protected Builder $query;
    protected Request $request;

   public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply(Builder $query): Builder
    {
        $this->query = $query;

        foreach($this->request->all() as $key => $value) {
            if (method_exists($this, $key) && $value !== null) {
                $this->$key($value);
            }
        }

        return $this->query;
    }
}