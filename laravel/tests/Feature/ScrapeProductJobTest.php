<?php

namespace Tests\Feature;

use App\Jobs\ScrapeProductJob;
use App\Models\PriceAlert;
use App\Services\ScraperService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
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

    public function test_successful_scrape_updates_price_history_and_triggers_alerts(): void
    {
        $product = $this->product([
            'current_price' => 100,
            'currency' => 'RON',
            'scrape_error' => 'Old error',
        ]);
        $user = $this->user();
        $this->attachProduct($user, $product);

        $targetAlert = $product->priceAlerts()->create([
            'user_id' => $user->id,
            'type' => PriceAlert::TYPE_TARGET_PRICE,
            'target_price' => 90,
        ]);
        $dropAlert = $product->priceAlerts()->create([
            'user_id' => $user->id,
            'type' => PriceAlert::TYPE_PRICE_DROP,
            'target_price' => null,
        ]);

        $scraper = Mockery::mock(ScraperService::class);
        $scraper->shouldReceive('scrape')
            ->once()
            ->with($product->url)
            ->andReturn(['price' => 80, 'currency' => 'RON']);

        (new ScrapeProductJob($product->id))->handle($scraper);

        $product->refresh();

        $this->assertSame('80.00', $product->current_price);
        $this->assertNull($product->scrape_error);
        $this->assertDatabaseHas('price_history', [
            'product_id' => $product->id,
            'price' => 80,
            'currency' => 'RON',
        ]);
        $this->assertNotNull($targetAlert->fresh()->triggered_at);
        $this->assertNotNull($dropAlert->fresh()->triggered_at);
    }

    public function test_scrape_without_price_change_updates_last_checked_only(): void
    {
        $product = $this->product([
            'current_price' => 100,
            'last_checked_at' => now()->subDay(),
            'scrape_error' => 'Old error',
        ]);

        $scraper = Mockery::mock(ScraperService::class);
        $scraper->shouldReceive('scrape')
            ->once()
            ->with($product->url)
            ->andReturn(['price' => 100, 'currency' => 'RON']);

        (new ScrapeProductJob($product->id))->handle($scraper);

        $this->assertSame(0, $product->priceHistory()->count());
        $this->assertNull($product->fresh()->scrape_error);
        $this->assertTrue($product->fresh()->last_checked_at->greaterThan($product->last_checked_at));
    }

    public function test_missing_product_does_nothing(): void
    {
        $scraper = Mockery::mock(ScraperService::class);
        $scraper->shouldNotReceive('scrape');

        (new ScrapeProductJob(999999))->handle($scraper);
        (new ScrapeProductJob(999999))->failed(new Exception('missing'));

        $this->assertDatabaseCount('products', 0);
    }
}
