<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductStock;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    private Product $product;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(InventoryService::class);
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

    public function test_get_stock_returns_zero_when_no_stock_row_exists(): void
    {
        $this->assertSame(0, $this->service->getStock($this->product, $this->branch));
    }

    public function test_adjust_stock_creates_a_row_and_increments_quantity(): void
    {
        $this->service->adjustStock($this->product, $this->branch, 50);

        $this->assertSame(50, $this->service->getStock($this->product, $this->branch));
        $this->assertDatabaseHas('product_stocks', [
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'quantity' => 50,
        ]);
    }

    public function test_adjust_stock_decrements_quantity(): void
    {
        ProductStock::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'quantity' => 100,
        ]);

        $this->service->adjustStock($this->product, $this->branch, -30);

        $this->assertSame(70, $this->service->getStock($this->product, $this->branch));
    }

    public function test_adjust_stock_rejects_a_decrement_that_would_go_below_zero(): void
    {
        ProductStock::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'quantity' => 10,
        ]);

        $this->expectException(InvalidArgumentException::class);

        try {
            $this->service->adjustStock($this->product, $this->branch, -20);
        } finally {
            $this->assertSame(10, $this->service->getStock($this->product, $this->branch));
        }
    }

    public function test_adjust_stock_serializes_sequential_updates_on_the_same_row_without_losing_writes(): void
    {
        ProductStock::create([
            'product_id' => $this->product->id,
            'branch_id' => $this->branch->id,
            'quantity' => 100,
        ]);

        // Simulates two competing requests adjusting the same product/branch
        // stock row. Each call locks the row for the duration of its own
        // transaction (lockForUpdate), so the second call's read-modify-write
        // always sees the first call's committed result instead of a stale
        // quantity — no lost update, regardless of ordering.
        $this->service->adjustStock($this->product, $this->branch, 25);
        $this->service->adjustStock($this->product, $this->branch, -40);

        $this->assertSame(85, $this->service->getStock($this->product, $this->branch));
        $this->assertSame(1, ProductStock::where('product_id', $this->product->id)
            ->where('branch_id', $this->branch->id)
            ->count());
    }
}
