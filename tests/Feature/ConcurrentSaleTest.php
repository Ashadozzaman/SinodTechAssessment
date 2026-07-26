<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Proves the SaleService::create() lockForUpdate() (app/Services/SaleService.php)
 * actually serializes two independent connections racing on the same
 * product_stocks row, rather than just asserting sequential behaviour.
 *
 * This doesn't use RefreshDatabase: that trait wraps each test in a single
 * outer transaction on the default connection, which would hide any writes
 * from a genuinely separate PDO connection. Instead this test migrates a
 * throwaway file-backed SQLite database (two connections can't share an
 * :memory: database) and tears it down itself.
 */
class ConcurrentSaleTest extends TestCase
{
    private string $dbFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbFile = tempnam(sys_get_temp_dir(), 'concurrent_sale_') . '.sqlite';

        config([
            'database.connections.race_a' => [
                'driver' => 'sqlite',
                'database' => $this->dbFile,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            // busy_timeout = 0 makes a locked write fail immediately instead
            // of waiting, so the test is deterministic instead of relying on
            // a sleep/retry race against connection A.
            'database.connections.race_b' => [
                'driver' => 'sqlite',
                'database' => $this->dbFile,
                'prefix' => '',
                'foreign_key_constraints' => true,
                'busy_timeout' => 0,
            ],
            'database.default' => 'race_a',
        ]);

        $this->artisan('migrate', ['--database' => 'race_a', '--force' => true])->run();
    }

    protected function tearDown(): void
    {
        DB::purge('race_a');
        DB::purge('race_b');
        @unlink($this->dbFile);

        parent::tearDown();
    }

    public function test_two_racing_connections_cannot_both_deduct_stock_past_zero(): void
    {
        $branch = Branch::create(['name' => 'Main', 'address' => '1 Main St', 'phone' => '0000000000']);
        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000000']);
        $cashier = User::factory()->create();
        $product = Product::create(['name' => 'Widget', 'sku' => 'WID-001', 'price' => 10.00]);

        // Only 10 in stock. Two competing 6-unit sales would oversell to -2
        // if the second connection were free to read the pre-decrement
        // quantity instead of blocking behind the first's lock.
        ProductStock::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 10]);

        // Connection A: stand in for the first in-flight request. Locks and
        // decrements the row exactly as SaleService does, but doesn't
        // commit yet.
        DB::connection('race_a')->beginTransaction();
        DB::connection('race_a')->table('product_stocks')
            ->where('product_id', $product->id)
            ->where('branch_id', $branch->id)
            ->lockForUpdate()
            ->first();
        DB::connection('race_a')->table('product_stocks')
            ->where('product_id', $product->id)
            ->where('branch_id', $branch->id)
            ->decrement('quantity', 6);

        // Connection B: a fully independent PDO connection standing in for a
        // second, truly concurrent request, attempting the same 6-unit
        // deduction while A's transaction is still open.
        $blocked = false;

        try {
            DB::connection('race_b')->beginTransaction();
            DB::connection('race_b')->table('product_stocks')
                ->where('product_id', $product->id)
                ->where('branch_id', $branch->id)
                ->lockForUpdate()
                ->first();
            DB::connection('race_b')->table('product_stocks')
                ->where('product_id', $product->id)
                ->where('branch_id', $branch->id)
                ->decrement('quantity', 6);
        } catch (QueryException $e) {
            $blocked = true;
            $this->assertStringContainsString('locked', strtolower($e->getMessage()));
            DB::connection('race_b')->rollBack();
        }

        $this->assertTrue($blocked, 'Expected connection B to be blocked by connection A\'s uncommitted lock instead of racing it.');

        // Only once A commits does the row become available again — and it
        // correctly reflects the first sale's deduction, not the stale
        // pre-transaction value.
        DB::connection('race_a')->commit();
        $this->assertSame(4, ProductStock::where('product_id', $product->id)->value('quantity'));

        // Replaying the second sale for real through SaleService now
        // correctly sees 4 in stock and rejects a 6-unit sale outright,
        // instead of the oversell that would have happened had the two
        // connections raced on the same pre-decrement read.
        $this->expectException(InsufficientStockException::class);

        try {
            app(SaleService::class)->create([
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'user_id' => $cashier->id,
                'items' => [['product_id' => $product->id, 'quantity' => 6]],
            ]);
        } finally {
            $this->assertSame(4, ProductStock::where('product_id', $product->id)->value('quantity'));
        }
    }
}
