<?php

namespace Tests\Feature;

use App\Models\PriceAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class ProductAndAlertTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_user_can_only_view_products_from_their_wishlist(): void
    {
        $owner = $this->actingAsUser($this->user());
        $product = $this->product();
        $this->attachProduct($owner, $product);

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.is_in_wishlist', true);

        $other = $this->actingAsUser($this->user());

        $this->getJson("/api/products/{$product->id}")
            ->assertForbidden();

        $admin = $this->actingAsUser($this->admin());

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.is_in_wishlist', false);
    }

    public function test_user_can_create_target_and_drop_alerts_for_owned_available_product(): void
    {
        $user = $this->actingAsUser($this->user());
        $product = $this->product(['current_price' => 100]);
        $this->attachProduct($user, $product);

        $this->postJson("/api/products/{$product->id}/alerts", [
            'type' => PriceAlert::TYPE_TARGET_PRICE,
            'target_price' => 80,
        ])->assertOk()
            ->assertJsonPath('data.type', PriceAlert::TYPE_TARGET_PRICE);

        $this->postJson("/api/products/{$product->id}/alerts", [
            'type' => PriceAlert::TYPE_PRICE_DROP,
        ])->assertOk()
            ->assertJsonPath('data.type', PriceAlert::TYPE_PRICE_DROP)
            ->assertJsonPath('data.target_price', null);

        $this->assertDatabaseHas('price_alerts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'type' => PriceAlert::TYPE_TARGET_PRICE,
        ]);

        $this->assertDatabaseHas('price_alerts', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'type' => PriceAlert::TYPE_PRICE_DROP,
        ]);
    }

    public function test_alerts_are_blocked_for_unavailable_or_not_owned_products(): void
    {
        $user = $this->actingAsUser($this->user());
        $ownedUnavailable = $this->product(['scrape_error' => 'Product unavailable']);
        $notOwned = $this->product();
        $this->attachProduct($user, $ownedUnavailable);

        $this->postJson("/api/products/{$ownedUnavailable->id}/alerts", [
            'type' => PriceAlert::TYPE_TARGET_PRICE,
            'target_price' => 50,
        ])->assertUnprocessable();

        $this->postJson("/api/products/{$notOwned->id}/alerts", [
            'type' => PriceAlert::TYPE_TARGET_PRICE,
            'target_price' => 50,
        ])->assertForbidden();
    }

    public function test_target_price_must_be_below_current_price(): void
    {
        $user = $this->actingAsUser($this->user());
        $product = $this->product(['current_price' => 100]);
        $this->attachProduct($user, $product);

        $this->postJson("/api/products/{$product->id}/alerts", [
            'type' => PriceAlert::TYPE_TARGET_PRICE,
            'target_price' => 100,
        ])->assertUnprocessable();
    }

    public function test_removing_from_wishlist_removes_only_that_users_alerts(): void
    {
        $owner = $this->actingAsUser($this->user());
        $other = $this->user();
        $product = $this->product();
        $this->attachProduct($owner, $product);
        $this->attachProduct($other, $product);

        $product->priceAlerts()->create([
            'user_id' => $owner->id,
            'type' => PriceAlert::TYPE_PRICE_DROP,
            'target_price' => null,
        ]);

        $product->priceAlerts()->create([
            'user_id' => $other->id,
            'type' => PriceAlert::TYPE_PRICE_DROP,
            'target_price' => null,
        ]);

        $this->deleteJson("/api/products/{$product->id}")
            ->assertOk();

        $this->assertDatabaseMissing('price_alerts', [
            'user_id' => $owner->id,
            'product_id' => $product->id,
        ]);

        $this->assertDatabaseHas('price_alerts', [
            'user_id' => $other->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_product_listing_filters_by_search_store_and_availability(): void
    {
        $user = $this->actingAsUser($this->user());
        $available = $this->product([
            'name' => 'Asus Monitor',
            'store' => 'emag',
            'scrape_error' => null,
        ]);
        $unavailable = $this->product([
            'name' => 'Asus Keyboard',
            'store' => 'emag',
            'scrape_error' => 'Product unavailable',
        ]);
        $otherStore = $this->product([
            'name' => 'Asus Mouse',
            'store' => 'altex',
            'scrape_error' => null,
        ]);
        $otherName = $this->product([
            'name' => 'Dell Monitor',
            'store' => 'emag',
            'scrape_error' => null,
        ]);

        foreach ([$available, $unavailable, $otherStore, $otherName] as $product) {
            $this->attachProduct($user, $product);
        }

        $this->getJson('/api/products?search=Asus&store=emag&status=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $available->id);

        $this->getJson('/api/products?store=emag&status=0')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $unavailable->id);
    }
}
