<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InventoryPermissionTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'inventory.adjust']);

        $this->product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-0001',
            'price' => 9.99,
        ]);
        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'address' => '123 Test St',
            'phone' => '0000000000',
        ]);
    }

    private function userWithPermission(string $permission): User
    {
        $role = Role::create(['name' => $permission . '-role']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->put(route('inventory.adjust', $this->product), ['branch_id' => $this->branch->id, 'delta' => 10])
            ->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_adjust_stock(): void
    {
        $this->actingAs(User::factory()->create());

        $this->put(route('inventory.adjust', $this->product), ['branch_id' => $this->branch->id, 'delta' => 10])
            ->assertForbidden();

        $this->assertDatabaseMissing('product_stocks', ['product_id' => $this->product->id]);
    }

    public function test_user_with_permission_can_adjust_stock(): void
    {
        $this->actingAs($this->userWithPermission('inventory.adjust'));

        $response = $this->put(route('inventory.adjust', $this->product), [
            'branch_id' => $this->branch->id,
            'delta' => 25,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'quantity' => 25,
        ]);
    }
}
