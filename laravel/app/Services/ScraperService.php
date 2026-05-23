<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ScraperService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.scraper.url', 'http://scraper:8000'), '/');
    }

    /**
     * Call the scraper microservice and return the raw response array.
     *
     * @throws RuntimeException
     */
    public function scrape(string $url): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->baseUrl}/scrape/", [
                'url' => $url,
            ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('We could not check this product right now. Please try again later.');
        }

        if ($response->failed()) {
            throw new RuntimeException($this->friendlyErrorMessage($response->status()));
        }

        return $response->json();
    }

    private function friendlyErrorMessage(int $status): string
    {
        return match ($status) {
            400 => 'This store is not supported yet.',
            404 => 'Product not found. Please check the URL and try again.',
            422 => 'We could not read this product. Please check the URL and try again.',
            default => 'We could not read this product right now. Please check the URL or try again later.',
        };
    }
}
