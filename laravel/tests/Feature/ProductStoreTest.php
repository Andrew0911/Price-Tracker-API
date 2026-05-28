<?php

namespace Tests\Feature;

use App\Services\ScraperService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class ProductStoreTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_user_can_add_new_product_from_scraper(): void
    {
        $user = $this->actingAsUser($this->user());
        $url = 'https://example.test/new-product';

        $scraper = Mockery::mock(ScraperService::class);
        $scraper->shouldReceive('scrape')
            ->once()
            ->with($url)
            ->andReturn([
                'name' => 'Scraped Product',
                'store' => 'emag',
                'image_url' => 'https://example.test/image.jpg',
                'price' => 249.99,
                'currency' => 'RON',
            ]);
        $this->app->instance(ScraperService::class, $scraper);

        $this->postJson('/api/products', ['url' => $url])
            ->assertOk()
            ->assertJsonPath('data.name', 'Scraped Product');

        $this->assertDatabaseHas('products', [
            'url' => $url,
            'name' => 'Scraped Product',
            'current_price' => 249.99,
        ]);
        $this->assertDatabaseHas('price_history', [
            'price' => 249.99,
            'currency' => 'RON',
        ]);
        $this->assertTrue($user->products()->where('url', $url)->exists());
    }

    public function test_user_can_attach_existing_product_without_scraping_again(): void
    {
        $user = $this->actingAsUser($this->user());
        $product = $this->product(['url' => 'https://example.test/existing']);

        $scraper = Mockery::mock(ScraperService::class);
        $scraper->shouldNotReceive('scrape');
        $this->app->instance(ScraperService::class, $scraper);

        $this->postJson('/api/products', ['url' => $product->url])
            ->assertOk()
            ->assertJsonPath('data.id', $product->id);

        $this->assertTrue($user->products()->where('products.id', $product->id)->exists());
    }

    public function test_duplicate_product_is_rejected_for_same_user(): void
    {
        $user = $this->actingAsUser($this->user());
        $product = $this->product();
        $this->attachProduct($user, $product);

        $this->postJson('/api/products', ['url' => $product->url])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Product with this URL is already tracked');
    }

    public function test_non_admin_cannot_add_proxy_required_store(): void
    {
        $this->actingAsUser($this->user());

        $this->postJson('/api/products', ['url' => 'https://www.emag.ro/product'])
            ->assertStatus(400)
            ->assertJsonPath('message', 'You do not have permission to add a product from this store');
    }

    public function test_admin_can_add_proxy_required_store(): void
    {
        $this->actingAsUser($this->admin());

        $url = 'https://www.emag.ro/product';

        $scraper = Mockery::mock(ScraperService::class);
        $scraper->shouldReceive('scrape')
            ->once()
            ->with($url)
            ->andReturn([
                'name' => 'Admin Product',
                'store' => 'emag',
                'image_url' => null,
                'price' => 100,
                'currency' => 'RON',
            ]);
        $this->app->instance(ScraperService::class, $scraper);

        $this->postJson('/api/products', ['url' => $url])
            ->assertOk()
            ->assertJsonPath('data.name', 'Admin Product');
    }

    public function test_scraper_exception_returns_failure_response(): void
    {
        $this->actingAsUser($this->user());
        $url = 'https://example.test/failure';

        $scraper = Mockery::mock(ScraperService::class);
        $scraper->shouldReceive('scrape')
            ->once()
            ->with($url)
            ->andThrow(new \RuntimeException('Scraper failed'));
        $this->app->instance(ScraperService::class, $scraper);

        $this->postJson('/api/products', ['url' => $url])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Scraper failed');
    }
}
