<?php

namespace Tests\Feature;

use App\Jobs\ScrapeProductJob;
use App\Models\PriceAlert;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class ScrapeProductJobTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_failed_scrape_marks_product_unavailable_and_deletes_alerts(): void
    {
        $product = $this->product();
        $user = $this->user();
        $this->attachProduct($user, $product);

        $product->priceAlerts()->create([
            'user_id' => $user->id,
            'type' => PriceAlert::TYPE_TARGET_PRICE,
            'target_price' => 80,
        ]);

        $product->priceAlerts()->create([
            'user_id' => $user->id,
            'type' => PriceAlert::TYPE_PRICE_DROP,
            'target_price' => null,
        ]);

        (new ScrapeProductJob($product->id))->failed(new Exception('Scrape failed'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'scrape_error' => 'Product unavailable',
        ]);

        $this->assertDatabaseMissing('price_alerts', [
            'product_id' => $product->id,
        ]);
    }
}
