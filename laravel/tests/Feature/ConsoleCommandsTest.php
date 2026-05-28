<?php

namespace Tests\Feature;

use App\Jobs\ScrapeProductJob;
use App\Mail\PriceAlertsMail;
use App\Models\PriceAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class ConsoleCommandsTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_scrape_products_dispatches_only_stale_tracked_products(): void
    {
        Queue::fake();

        $user = $this->user();
        $staleTracked = $this->product(['last_checked_at' => now()->subHours(9)]);
        $freshTracked = $this->product(['last_checked_at' => now()]);
        $untracked = $this->product(['last_checked_at' => now()->subHours(9)]);
        $neverChecked = $this->product(['last_checked_at' => null]);

        $this->attachProduct($user, $staleTracked);
        $this->attachProduct($user, $freshTracked);
        $this->attachProduct($user, $neverChecked);

        $this->artisan('scrape:products')->assertSuccessful();

        Queue::assertPushedOn('cron-scraping', ScrapeProductJob::class);
        Queue::assertPushed(ScrapeProductJob::class, 2);
        Queue::assertPushed(ScrapeProductJob::class, fn (ScrapeProductJob $job) => $job->productId === $staleTracked->id);
        Queue::assertPushed(ScrapeProductJob::class, fn (ScrapeProductJob $job) => $job->productId === $neverChecked->id);
        Queue::assertNotPushed(ScrapeProductJob::class, fn (ScrapeProductJob $job) => $job->productId === $freshTracked->id);
        Queue::assertNotPushed(ScrapeProductJob::class, fn (ScrapeProductJob $job) => $job->productId === $untracked->id);
    }

    public function test_scrape_products_handles_no_matching_products(): void
    {
        Queue::fake();

        $this->artisan('scrape:products')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_alerts_notify_sends_grouped_emails_and_marks_alerts(): void
    {
        Mail::fake();

        $user = $this->user(['email' => 'alerts@example.test']);
        $product = $this->product(['current_price' => 80]);
        $this->attachProduct($user, $product);

        $this->addHistory($product, 100, '2026-05-28 10:00:00');
        $this->addHistory($product, 80, '2026-05-28 11:00:00');

        $targetAlert = $product->priceAlerts()->create([
            'user_id' => $user->id,
            'type' => PriceAlert::TYPE_TARGET_PRICE,
            'target_price' => 90,
            'triggered_at' => '2026-05-28 11:00:00',
        ]);
        $dropAlert = $product->priceAlerts()->create([
            'user_id' => $user->id,
            'type' => PriceAlert::TYPE_PRICE_DROP,
            'target_price' => null,
            'triggered_at' => '2026-05-28 11:00:00',
        ]);

        $this->artisan('alerts:notify')->assertSuccessful();

        Mail::assertQueued(PriceAlertsMail::class, function (PriceAlertsMail $mail) use ($user) {
            return $mail->hasTo($user->email)
                && count($mail->alerts) === 1
                && $mail->alerts[0]['old_price'] === 100.0
                && $mail->alerts[0]['price'] === 80.0;
        });

        $this->assertNotNull($targetAlert->fresh()->notified_at);
        $this->assertNotNull($dropAlert->fresh()->notified_at);
        $this->assertNull($dropAlert->fresh()->triggered_at);
    }

    public function test_alerts_notify_handles_no_pending_alerts(): void
    {
        Mail::fake();

        $this->artisan('alerts:notify')->assertSuccessful();

        Mail::assertNothingQueued();
    }
}
