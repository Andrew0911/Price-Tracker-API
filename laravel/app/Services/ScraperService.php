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
            throw new RuntimeException("Scraper service unavailable");
        }

        if ($response->failed()) {
            $detail = $response->json('detail') ?? $response->body();
            throw new RuntimeException($detail);
        }

        return $response->json();
    }
}