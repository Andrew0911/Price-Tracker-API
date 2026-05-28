<?php

namespace Tests\Feature;

use App\Mail\PriceAlertsMail;
use App\Models\PriceAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class PriceAlertsMailTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_price_alert_mail_renders_target_and_drop_alerts(): void
    {
        $user = $this->user(['name' => 'Ada']);
        $targetProduct = $this->product([
            'name' => 'Target Product',
            'url' => 'https://example.test/target',
            'current_price' => 80,
        ]);
        $dropProduct = $this->product([
            'name' => 'Drop Product',
            'url' => 'https://example.test/drop',
            'current_price' => 70,
        ]);

        $targetAlert = $targetProduct->priceAlerts()->create([
            'user_id' => $user->id,
            'type' => PriceAlert::TYPE_TARGET_PRICE,
            'target_price' => 90,
        ]);
        $dropAlert = $dropProduct->priceAlerts()->create([
            'user_id' => $user->id,
            'type' => PriceAlert::TYPE_PRICE_DROP,
            'target_price' => null,
        ]);

        $html = (new PriceAlertsMail($user, [
            [
                'product' => $targetProduct,
                'alert' => $targetAlert,
                'old_price' => 100,
                'price' => 80,
            ],
            [
                'product' => $dropProduct,
                'alert' => $dropAlert,
                'old_price' => null,
                'price' => 70,
            ],
        ]))->render();

        $this->assertStringContainsString('Price alerts were triggered', $html);
        $this->assertStringContainsString('Hi Ada', $html);
        $this->assertStringContainsString('Target Product', $html);
        $this->assertStringContainsString('Drop Product', $html);
        $this->assertStringContainsString('100.00 RON', $html);
        $this->assertStringContainsString('80.00 RON', $html);
        $this->assertStringContainsString('90.00 RON', $html);
        $this->assertStringContainsString('70.00 RON', $html);
    }

    public function test_price_alert_mail_subject_depends_on_alert_count(): void
    {
        $user = $this->user();
        $product = $this->product();
        $alert = $product->priceAlerts()->create([
            'user_id' => $user->id,
            'type' => PriceAlert::TYPE_PRICE_DROP,
            'target_price' => null,
        ]);

        $single = new PriceAlertsMail($user, [[
            'product' => $product,
            'alert' => $alert,
            'old_price' => null,
            'price' => 100,
        ]]);
        $single->build();

        $multiple = new PriceAlertsMail($user, [
            [
                'product' => $product,
                'alert' => $alert,
                'old_price' => null,
                'price' => 100,
            ],
            [
                'product' => $product,
                'alert' => $alert,
                'old_price' => null,
                'price' => 100,
            ],
        ]);
        $multiple->build();

        $this->assertSame('Price alert - a product needs your attention', $single->subject);
        $this->assertSame('Price alerts - 2 products need your attention', $multiple->subject);
    }
}
