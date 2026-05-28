<?php

namespace App\Jobs;

use App\Models\PriceAlert;
use App\Models\Product;
use App\Services\ScraperService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScrapeProductJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public readonly int $productId) {}

    public function handle(ScraperService $scraper): void
    {
        $product = Product::find($this->productId);

        if (!$product) {
            return;
        }

        $result = $scraper->scrape($product->url);

        // TEST ALERT EMAIL
        // if($product->id == 15){
        //     $result['price'] = 27999.99;
        // }

        $now = now();
        $previousPrice = $product->current_price !== null
            ? (float) $product->current_price
            : null;
        $newPrice = (float) $result['price'];

        $priceChanged = $previousPrice === null || $previousPrice !== $newPrice;
        $priceDropped = $previousPrice !== null && $newPrice < $previousPrice;

        if ($priceChanged) {
            // If price changed, add history and update current price on product entity
            $product->priceHistory()->create([
                'price' => $newPrice,
                'currency' => $result['currency'],
                'checked_at' => $now,
            ]);

            $product->update([
                'current_price' => $newPrice,
                'currency' => $result['currency'],
                'last_checked_at' => $now,
                'scrape_error' => null,
            ]);
        } else {
            // If price didn't change, only mark the last checked date & clean previous errors
            $product->update([
                'last_checked_at' => $now,
                'scrape_error' => null,
            ]);
        }

        // Check active price alerts and mark triggered_at so alerts:notify picks them up.
        $product->priceAlerts()
            ->whereNull('triggered_at')
            ->where('type', PriceAlert::TYPE_TARGET_PRICE)
            ->where('target_price', '>=', $newPrice)
            ->each(function ($alert) use ($newPrice, $product, $now) {
                $alert->update(['triggered_at' => $now]);

                Log::info("Target price alert triggered for user #{$alert->user_id} product #{$product->id}: {$newPrice} <= {$alert->target_price}");
            });

        if ($priceDropped) {
            $product->priceAlerts()
                ->whereNull('triggered_at')
                ->where('type', PriceAlert::TYPE_PRICE_DROP)
                ->each(function ($alert) use ($previousPrice, $newPrice, $product, $now) {
                    $alert->update([
                        'triggered_at' => $now,
                        'notified_at' => null,
                    ]);

                    Log::info("Price drop alert triggered for user #{$alert->user_id} product #{$product->id}: {$previousPrice} -> {$newPrice}");
                });
        }
    }

    public function failed(Throwable $e): void
    {
        $product = Product::find($this->productId);

        if (!$product) {
            return;
        }

        $product->update([
            'scrape_error' => 'Product unavailable',
            'last_checked_at' => now(),
        ]);

        $deletedAlerts = $product->priceAlerts()->delete();

        Log::error("ScrapeProductJob failed for product #{$this->productId}", [
            'error' => $e->getMessage(),
            'deleted_alerts' => $deletedAlerts,
        ]);
    }
}
