<?php

namespace Tests\Feature;

use App\Enums\SaleStatus;
use App\Events\SaleCompleted;
use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private SaleService $service;

    private Branch $branch;

    private Customer $customer;

    private User $cashier;

    private Product $productA;

    private Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SaleService::class);

        $this->branch = Branch::create(['name' => 'Main', 'address' => '1 Main St', 'phone' => '0000000000']);
        $this->customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000000']);
        $this->cashier = User::factory()->create();

        $this->productA = Product::create(['name' => 'Widget', 'sku' => 'WID-001', 'price' => 10.00]);
        $this->productB = Product::create(['name' => 'Gadget', 'sku' => 'GAD-001', 'price' => 25.50]);

        ProductStock::create(['product_id' => $this->productA->id, 'branch_id' => $this->branch->id, 'quantity' => 20]);
        ProductStock::create(['product_id' => $this->productB->id, 'branch_id' => $this->branch->id, 'quantity' => 5]);
    }

    private function saleData(array $items): array
    {
        return [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'items' => $items,
        ];
    }

    public function test_successful_sale_deducts_stock_and_creates_correct_records(): void
    {
        $sale = $this->service->create($this->saleData([
            ['product_id' => $this->productA->id, 'quantity' => 3],
            ['product_id' => $this->productB->id, 'quantity' => 2],
        ]));

        $this->assertSame(17, ProductStock::where('product_id', $this->productA->id)->value('quantity'));
        $this->assertSame(3, ProductStock::where('product_id', $this->productB->id)->value('quantity'));

        $this->assertSame(SaleStatus::Completed, $sale->status);
        $this->assertEqualsWithDelta(81.00, (float) $sale->total_amount, 0.001); // 3*10 + 2*25.50
        $this->assertNotEmpty($sale->invoice_number);
        $this->assertCount(2, $sale->items);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $this->productA->id,
            'quantity' => 3,
            'unit_price' => 10.00,
            'subtotal' => 30.00,
        ]);
    }

    public function test_selling_more_than_available_stock_is_fully_rejected_with_zero_stock_change(): void
    {
        $this->expectException(InsufficientStockException::class);

        try {
            $this->service->create($this->saleData([
                ['product_id' => $this->productA->id, 'quantity' => 5],
                // Only 5 in stock — this line should trigger the rollback.
                ['product_id' => $this->productB->id, 'quantity' => 999],
            ]));
        } finally {
            // Neither line's stock should have moved — the first line's
            // deduction must have rolled back along with the second's failure.
            $this->assertSame(20, ProductStock::where('product_id', $this->productA->id)->value('quantity'));
            $this->assertSame(5, ProductStock::where('product_id', $this->productB->id)->value('quantity'));
            $this->assertDatabaseCount('sales', 0);
            $this->assertDatabaseCount('sale_items', 0);
        }
    }

    public function test_sale_completed_event_fires_exactly_once_on_success(): void
    {
        Event::fake();

        $this->service->create($this->saleData([
            ['product_id' => $this->productA->id, 'quantity' => 1],
        ]));

        Event::assertDispatchedTimes(SaleCompleted::class, 1);
    }

    public function test_sale_completed_event_does_not_fire_when_sale_fails(): void
    {
        Event::fake();

        try {
            $this->service->create($this->saleData([
                ['product_id' => $this->productB->id, 'quantity' => 999],
            ]));
        } catch (InsufficientStockException) {
            // expected
        }

        Event::assertNotDispatched(SaleCompleted::class);
    }
}
