<?php

namespace Tests\Feature\Concerns;

use App\Models\Friendship;
use App\Models\PriceHistory;
use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

trait CreatesTestData
{
    protected function user(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function admin(array $attributes = []): User
    {
        return $this->user(array_merge(['is_admin' => true], $attributes));
    }

    protected function actingAsUser(User $user): User
    {
        Sanctum::actingAs($user);

        return $user;
    }

    protected function product(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Test Product',
            'url' => 'https://example.test/products/' . uniqid(),
            'image_url' => null,
            'store' => 'test-store',
            'current_price' => 100,
            'currency' => 'RON',
            'last_checked_at' => now(),
        ], $attributes));
    }

    protected function attachProduct(User $user, Product $product): void
    {
        $user->products()->attach($product->id);
    }

    protected function addHistory(Product $product, float $price, ?string $checkedAt = null): PriceHistory
    {
        return $product->priceHistory()->create([
            'price' => $price,
            'currency' => $product->currency,
            'checked_at' => $checkedAt ? now()->parse($checkedAt) : now(),
        ]);
    }

    protected function makeFriends(User $a, User $b): void
    {
        Friendship::create([
            'user_id' => $a->id,
            'friend_id' => $b->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);

        Friendship::create([
            'user_id' => $b->id,
            'friend_id' => $a->id,
            'status' => Friendship::STATUS_ACCEPTED,
        ]);
    }
}
