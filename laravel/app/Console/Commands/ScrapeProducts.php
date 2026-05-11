<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeProductJob;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ScrapeProducts extends Command
{
    protected $signature = 'scrape:products';
    protected $description = 'Dispatch scrape jobs for all tracked products';

    public function handle(): int
    {
        $count = 0;

        $timeThreshold = now()->subHours(8);

        // Check products that haven't been cheked for over than an hour
        $products = Product::query()
            ->where('last_checked_at', '<=', $timeThreshold)
            ->orWhereNull('last_checked_at')
            ->has('users') // Scrape only products with at least one user associated
            ->limit(5000) // Batches of 5000 to protect the queue
            ->select('id')
            ->cursor();

        // TEST SCRAPE
        // $products = Product::query()
        //     ->where('id', 15)
        //     ->select('id')
        //     ->cursor();
                
        foreach ($products as $product) {
            ScrapeProductJob::dispatch($product->id)->onQueue('cron-scraping');
            $count++;
        }

        if ($count === 0) {
            Log::info('No products to scrape.');
            return self::SUCCESS;
        }

        Log::info("Dispatched {$count} scrape job(s).");

        return self::SUCCESS;
    }
}
