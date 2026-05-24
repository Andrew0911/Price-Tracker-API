<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceAlert extends Model
{
    public const TYPE_TARGET_PRICE = 'target_price';
    public const TYPE_PRICE_DROP = 'price_drop';

    protected $table = 'price_alerts';

    protected $fillable = [
        'user_id',
        'product_id',
        'type',
        'target_price',
        'notified_at',
        'triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'target_price' => 'decimal:2',
            'notified_at' => 'datetime',
            'triggered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
