<?php

namespace Tests\Feature;

use App\Services\ScraperService;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class ProductStoreExceptionTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_product_store_does_not_leak_database_errors(): void
    {
        $this->actingAsUser($this->user());

        $this->mock(ScraperService::class, function ($mock) {
            $mock->shouldReceive('scrape')
                ->once()
                ->andThrow(new QueryException(
                    'mysql',
                    'insert into products (url) values (?)',
                    ['https://example.test/product'],
                    new Exception('SQLSTATE[23000]: Integrity constraint violation')
                ));
        });

        $response = $this->postJson('/api/products', [
            'url' => 'https://example.test/product',
        ])
            ->assertInternalServerError()
            ->assertExactJson([
                'message' => 'Server error',
            ]);

        $body = $response->getContent();

        $this->assertStringNotContainsString('SQLSTATE', $body);
        $this->assertStringNotContainsString('insert into products', $body);
        $this->assertStringNotContainsString('https://example.test/product', $body);
    }

    public function test_product_store_still_returns_friendly_scraper_errors(): void
    {
        $this->actingAsUser($this->user());

        $this->mock(ScraperService::class, function ($mock) {
            $mock->shouldReceive('scrape')
                ->once()
                ->andThrow(new RuntimeException('We could not check this product right now. Please try again later.'));
        });

        $this->postJson('/api/products', [
            'url' => 'https://example.test/product',
        ])
            ->assertBadRequest()
            ->assertJsonPath('message', 'We could not check this product right now. Please try again later.');
    }
}
