<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use App\Services\CrmService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KpiTrackingTest extends TestCase
{
    use RefreshDatabase;

    private SaleService $saleService;

    private CrmService $crmService;

    private Branch $branch;

    private User $cashier;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->saleService = app(SaleService::class);
        $this->crmService = app(CrmService::class);

        $this->branch = Branch::create(['name' => 'Main', 'address' => '1 Main St', 'phone' => '0000000000']);
        $this->cashier = User::factory()->create();
        $this->product = Product::create(['name' => 'Widget', 'sku' => 'WID-100', 'price' => 10.00]);

        ProductStock::create(['product_id' => $this->product->id, 'branch_id' => $this->branch->id, 'quantity' => 100]);
    }

    private function saleFor(Customer $customer): void
    {
        $this->saleService->create([
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 1],
            ],
        ]);
    }

    public function test_kpi_score_increments_and_assignment_resolves_when_assigned_customer_buys(): void
    {
        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000020']);
        $employee = User::factory()->create(['kpi_score' => 0]);
        $admin = User::factory()->create();

        $assignment = $this->crmService->assignCustomer($customer, $employee, $admin);

        $this->saleFor($customer);

        $this->assertSame((int) config('crm.kpi_award_points'), $employee->fresh()->kpi_score);
        $this->assertSame(AssignmentStatus::Resolved, $assignment->fresh()->status);
        $this->assertNotNull($assignment->fresh()->resolved_at);
    }

    public function test_kpi_score_unchanged_when_customer_has_no_active_assignment(): void
    {
        $customer = Customer::create(['name' => 'John Doe', 'phone' => '01700000021']);
        $employee = User::factory()->create(['kpi_score' => 0]);

        $this->saleFor($customer);

        $this->assertSame(0, $employee->fresh()->kpi_score);
    }
}
