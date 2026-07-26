<?php

namespace Tests\Feature;

use App\Enums\SaleStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPurchaseHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function makeSale(Customer $customer, Branch $branch, User $cashier, string $invoiceNumber, string $saleDate): Sale
    {
        return Sale::create([
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'user_id' => $cashier->id,
            'invoice_number' => $invoiceNumber,
            'total_amount' => 50.00,
            'status' => SaleStatus::Completed,
            'sale_date' => $saleDate,
        ]);
    }

    public function test_last_purchase_at_and_purchase_frequency_are_correct(): void
    {
        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000000']);
        $branch = Branch::create(['name' => 'Main', 'address' => '1 Main St', 'phone' => '0000000000']);
        $cashier = User::factory()->create();

        $this->makeSale($customer, $branch, $cashier, 'INV-001', '2026-01-10 10:00:00');
        $this->makeSale($customer, $branch, $cashier, 'INV-002', '2026-03-15 10:00:00');
        $latest = $this->makeSale($customer, $branch, $cashier, 'INV-003', '2026-05-01 10:00:00');

        $this->assertSame(3, $customer->purchaseFrequency());
        $this->assertNotNull($customer->lastPurchaseAt());
        $this->assertTrue($customer->lastPurchaseAt()->equalTo($latest->sale_date));
    }

    public function test_customer_with_no_sales_returns_null_and_zero(): void
    {
        $customer = Customer::create(['name' => 'No Sales', 'phone' => '01700000001']);

        $this->assertNull($customer->lastPurchaseAt());
        $this->assertSame(0, $customer->purchaseFrequency());
    }

    public function test_show_page_returns_paginated_sales_and_stats(): void
    {
        $role = \Spatie\Permission\Models\Role::create(['name' => 'viewer']);
        \Spatie\Permission\Models\Permission::create(['name' => 'customers.view']);
        $role->givePermissionTo('customers.view');

        $actor = User::factory()->create();
        $actor->assignRole($role);

        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000002']);
        $branch = Branch::create(['name' => 'Main', 'address' => '1 Main St', 'phone' => '0000000000']);
        $this->makeSale($customer, $branch, $actor, 'INV-100', '2026-02-01 10:00:00');

        $response = $this->actingAs($actor)->get("/customers/{$customer->id}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('sales.data', 1)
            ->where('sales.data.0.invoice_number', 'INV-100')
            ->where('purchaseFrequency', 1)
            ->has('lastPurchaseAt')
        );
    }
}
