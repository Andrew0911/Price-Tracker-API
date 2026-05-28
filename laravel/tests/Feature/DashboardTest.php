<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_dashboard_returns_top_ten_movements_and_stores(): void
    {
        $user = $this->actingAsUser($this->user());

        for ($i = 1; $i <= 12; $i++) {
            $drop = $this->product([
                'name' => "Drop {$i}",
                'store' => "drop-store-{$i}",
                'current_price' => 100 - $i,
            ]);
            $this->attachProduct($user, $drop);
            $this->addHistory($drop, 100, '2026-01-01 00:00:00');
            $this->addHistory($drop, 100 - $i, '2026-01-02 00:00:00');

            $increase = $this->product([
                'name' => "Increase {$i}",
                'store' => "increase-store-{$i}",
                'current_price' => 100 + $i,
            ]);
            $this->attachProduct($user, $increase);
            $this->addHistory($increase, 100, '2026-01-01 00:00:00');
            $this->addHistory($increase, 100 + $i, '2026-01-02 00:00:00');
        }

        $response = $this->getJson('/api/dashboard/stats')
            ->assertOk();

        $this->assertCount(10, $response->json('data.top_price_drops'));
        $this->assertCount(10, $response->json('data.top_price_increases'));
        $this->assertCount(10, $response->json('data.store_breakdown'));

        $this->assertSame('Drop 12', $response->json('data.top_price_drops.0.name'));
        $this->assertSame('Increase 12', $response->json('data.top_price_increases.0.name'));
    }
}
