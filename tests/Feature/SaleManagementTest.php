<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaleManagementTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private Customer $customer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        collect(['sales.view', 'sales.create', 'sales.update', 'sales.delete'])
            ->each(fn (string $permission) => Permission::create(['name' => $permission]));

        $this->branch = Branch::create(['name' => 'Main', 'address' => '1 Main St', 'phone' => '0000000000']);
        $this->customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000000']);
        $this->product = Product::create(['name' => 'Widget', 'sku' => 'WID-001', 'price' => 10.00]);
        ProductStock::create(['product_id' => $this->product->id, 'branch_id' => $this->branch->id, 'quantity' => 5]);
    }

    protected function userWithPermission(string $permission): User
    {
        $role = Role::create(['name' => $permission . '-role']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/sales')->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_view_index(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/sales')->assertForbidden();
    }

    public function test_user_with_create_permission_can_complete_a_sale(): void
    {
        $actor = $this->userWithPermission('sales.create');

        $response = $this->actingAs($actor)->post('/sales', [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('sales', [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'user_id' => $actor->id,
            'total_amount' => 20.00,
        ]);
        $this->assertSame(3, ProductStock::where('product_id', $this->product->id)->value('quantity'));
    }

    public function test_user_without_create_permission_cannot_store_a_sale(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/sales', [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ])->assertForbidden();

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_overselling_via_the_controller_returns_a_clean_error_with_no_stock_change(): void
    {
        $actor = $this->userWithPermission('sales.create');

        $response = $this->actingAs($actor)->post('/sales', [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 999],
            ],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(5, ProductStock::where('product_id', $this->product->id)->value('quantity'));
    }

    public function test_the_cashier_is_taken_from_the_authenticated_user_not_client_input(): void
    {
        $actor = $this->userWithPermission('sales.create');
        $otherUser = User::factory()->create();

        $this->actingAs($actor)->post('/sales', [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'user_id' => $otherUser->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ]);

        $this->assertDatabaseHas('sales', ['user_id' => $actor->id]);
        $this->assertDatabaseMissing('sales', ['user_id' => $otherUser->id]);
    }
}
