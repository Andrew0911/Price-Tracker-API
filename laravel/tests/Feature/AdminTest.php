<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreatesTestData;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use CreatesTestData;
    use RefreshDatabase;

    public function test_admin_product_routes_are_for_admins_only(): void
    {
        $product = $this->product();

        $this->actingAsUser($this->user());

        $this->getJson('/api/admin/products')->assertForbidden();
        $this->deleteJson("/api/admin/products/{$product->id}")->assertForbidden();

        $this->actingAsUser($this->admin());

        $this->getJson('/api/admin/products')
            ->assertOk()
            ->assertJsonStructure(['data', 'page', 'lastPage', 'perPage', 'total']);

        $this->deleteJson("/api/admin/products/{$product->id}")
            ->assertOk();

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_admin_can_promote_and_delete_other_users_but_not_self(): void
    {
        $admin = $this->actingAsUser($this->admin());
        $user = $this->user(['is_admin' => false]);

        $this->patchJson("/api/admin/users/{$user->id}/promote")
            ->assertOk()
            ->assertJsonPath('data.is_admin', true);

        $this->deleteJson("/api/admin/users/{$admin->id}")
            ->assertUnprocessable();

        $this->deleteJson("/api/admin/users/{$user->id}")
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_user_management_supports_search_and_show(): void
    {
        $this->actingAsUser($this->admin());
        $matched = $this->user([
            'name' => 'Maria Searchable',
            'email' => 'maria@example.test',
        ]);
        $this->user([
            'name' => 'Other User',
            'email' => 'other@example.test',
        ]);

        $this->getJson('/api/admin/users?search=Maria')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matched->id);

        $this->getJson("/api/admin/users/{$matched->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $matched->id)
            ->assertJsonPath('data.email', 'maria@example.test');
    }

    public function test_admin_product_management_supports_filters(): void
    {
        $this->actingAsUser($this->admin());
        $matched = $this->product([
            'name' => 'Admin Asus Monitor',
            'store' => 'emag',
            'scrape_error' => null,
        ]);
        $this->product([
            'name' => 'Admin Asus Keyboard',
            'store' => 'emag',
            'scrape_error' => 'Product unavailable',
        ]);
        $this->product([
            'name' => 'Admin Asus Mouse',
            'store' => 'altex',
            'scrape_error' => null,
        ]);

        $this->getJson('/api/admin/products?search=Asus&store=emag&status=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matched->id);
    }
}
