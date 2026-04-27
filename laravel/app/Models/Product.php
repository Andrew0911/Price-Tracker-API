<?php

namespace App\Models;

use App\Traits\HasFilters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFilters;
    protected $table = 'products';
    protected $fillable = [
        'name',
        'url',
        'image_url',
        'store',
        'current_price',
        'currency',
        'last_checked_at',
        'scrape_error',
    ];

    protected function casts(): array
    {
        return [
            'current_price' => 'decimal:2',
            'last_checked_at' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function priceHistory(): HasMany
    {
        return $this->hasMany(PriceHistory::class);
    }

    public function priceAlerts(): HasMany
    {
        return $this->hasMany(PriceAlert::class);
    }
}
