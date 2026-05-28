<?php

namespace Tests\Unit;

use App\Services\ScraperService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ScraperServiceTest extends TestCase
{
    public function test_scrape_returns_scraper_payload(): void
    {
        config(['services.scraper.url' => 'http://scraper.test']);

        Http::fake([
            'scraper.test/scrape/' => Http::response([
                'name' => 'Product',
                'price' => 123.45,
                'currency' => 'RON',
            ]),
        ]);

        $result = (new ScraperService())->scrape('https://store.test/product');

        $this->assertSame('Product', $result['name']);
        $this->assertSame(123.45, $result['price']);

        Http::assertSent(fn ($request) =>
            $request->url() === 'http://scraper.test/scrape/'
            && $request['url'] === 'https://store.test/product'
        );
    }

    public function test_scrape_throws_friendly_messages_for_failed_responses(): void
    {
        config(['services.scraper.url' => 'http://scraper.test']);

        Http::fakeSequence()
            ->push([], 400)
            ->push([], 404)
            ->push([], 422)
            ->push([], 500);

        $cases = [
            400 => 'This store is not supported yet.',
            404 => 'Product not found. Please check the URL and try again.',
            422 => 'We could not read this product. Please check the URL and try again.',
            500 => 'We could not read this product right now. Please check the URL or try again later.',
        ];

        foreach ($cases as $status => $message) {
            try {
                (new ScraperService())->scrape('https://store.test/product');
                $this->fail("Expected RuntimeException for {$status}.");
            } catch (RuntimeException $exception) {
                $this->assertSame($message, $exception->getMessage());
            }
        }
    }

    public function test_scrape_handles_connection_errors(): void
    {
        config(['services.scraper.url' => 'http://scraper.test']);

        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('We could not check this product right now. Please try again later.');

        (new ScraperService())->scrape('https://store.test/product');
    }
}
