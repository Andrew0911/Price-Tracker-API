<?php

namespace App\Jobs;

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

        $priceChanged = (float) $product->current_price !== (float) $result['price'];

        if ($priceChanged) {
            // If price changed, add history and update current price on product entity
            $product->priceHistory()->create([
                'price' => $result['price'],
                'currency' => $result['currency'],
                'checked_at' => $now,
            ]);

            $product->update([
                'current_price' => $result['price'],
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

        // Check active price alerts — mark triggered_at so alerts:notify picks them up
        $product->priceAlerts()
            ->whereNull('triggered_at')
            ->where('target_price', '>=', $result['price'])
            ->each(function ($alert) use ($result, $product, $now) {
                $alert->update(['triggered_at' => $now]);

                Log::info("Price alert triggered for user #{$alert->user_id} product #{$product->id}: {$result['price']} <= {$alert->target_price}");
            });
    }

    public function failed(Throwable $e): void
    {
        Product::where('id', $this->productId)
            ->update(['scrape_error' => 'Product unavailable']);

        Log::error("ScrapeProductJob failed for product #{$this->productId}", [
            'error' => $e->getMessage(),
        ]);
    }
}
