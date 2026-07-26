<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LostCustomerTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        collect(['customers.view', 'customers.lost'])
            ->each(fn (string $permission) => Permission::create(['name' => $permission]));

        $this->branch = Branch::create(['name' => 'Main', 'address' => '1 Main St', 'phone' => '0000000000']);
        $this->cashier = User::factory()->create();
    }

    protected function userWithPermission(string $permission): User
    {
        $role = Role::create(['name' => $permission . '-role']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function saleFor(Customer $customer, string $saleDate): void
    {
        $customer->sales()->create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'invoice_number' => 'INV-' . uniqid(),
            'total_amount' => 10,
            'status' => 'completed',
            'sale_date' => $saleDate,
        ]);
    }

    public function test_lost_scope_includes_customer_whose_last_sale_was_91_days_ago(): void
    {
        $customer = Customer::create(['name' => 'Old Buyer', 'phone' => '01700000001']);
        $this->saleFor($customer, now()->subDays(91));

        $this->assertTrue(Customer::lost()->whereKey($customer->id)->exists());
    }

    public function test_lost_scope_excludes_customer_whose_last_sale_was_89_days_ago(): void
    {
        $customer = Customer::create(['name' => 'Recent Buyer', 'phone' => '01700000002']);
        $this->saleFor($customer, now()->subDays(89));

        $this->assertFalse(Customer::lost()->whereKey($customer->id)->exists());
    }

    public function test_lost_scope_includes_customer_with_no_sales_at_all(): void
    {
        $customer = Customer::create(['name' => 'Never Bought', 'phone' => '01700000003']);

        $this->assertTrue(Customer::lost()->whereKey($customer->id)->exists());
    }

    public function test_user_without_permission_cannot_view_lost_customers_page(): void
    {
        $this->actingAs($this->userWithPermission('customers.view'));

        $this->get('/lost-customers')->assertForbidden();
    }

    public function test_user_with_permission_can_view_lost_customers_page(): void
    {
        $customer = Customer::create(['name' => 'Old Buyer', 'phone' => '01700000004']);
        $this->saleFor($customer, now()->subDays(120));

        $this->actingAs($this->userWithPermission('customers.lost'));

        $response = $this->get('/lost-customers');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Old Buyer')
        );
    }
}
