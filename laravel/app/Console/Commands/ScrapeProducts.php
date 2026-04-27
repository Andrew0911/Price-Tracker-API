<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeProductJob;
use App\Models\Product;
use Illuminate\Console\Command;

class ScrapeProducts extends Command
{
    protected $signature = 'scrape:products';
    protected $description = 'Dispatch scrape jobs for all tracked products';

    public function handle(): int
    {
        $count = 0;

        foreach (Product::query()->select('id')->cursor() as $product) {
            ScrapeProductJob::dispatch($product->id)->onQueue('cron-scraping');
            $count++;
        }

        if ($count === 0) {
            $this->info('No products to scrape.');
            return self::SUCCESS;
        }

        $this->info("Dispatched {$count} scrape job(s).");

        return self::SUCCESS;
    }
}
