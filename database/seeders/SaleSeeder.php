<?php

namespace Database\Seeders;

use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use App\Services\SaleService;
use Carbon\Carbon;
use Database\Seeders\Concerns\PicksSellableStock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaleSeeder extends Seeder
{
    use PicksSellableStock;

    public function __construct(private readonly SaleService $saleService) {}

    /**
     * Run the database seeds.
     *
     * Gives ~65% of customers a purchase history through the real
     * SaleService::create() path (so stock deduction and the SaleCompleted
     * listeners all run exactly as they would in production), then
     * backdates each sale so a realistic mix exists out of the box: some
     * customers with a recent purchase (active), most with a last purchase
     * 91-360 days ago (lost per Customer::scopeLost()), and the remaining
     * ~35% of customers left with zero sales (also lost).
     */
    public function run(): void
    {
        $customers = Customer::orderBy('id')->get();
        $branches = Branch::all();
        $sellers = User::role(['Employee', 'Manager'])->get();

        if ($customers->isEmpty() || $branches->isEmpty() || $sellers->isEmpty()) {
            return;
        }

        $customersWithHistory = $customers->random((int) ceil($customers->count() * 0.65))->values();

        foreach ($customersWithHistory as $index => $customer) {
            $saleCount = random_int(1, 3);

            for ($i = 0; $i < $saleCount; $i++) {
                // Every 3rd purchasing customer's most recent sale lands in
                // the last 30 days (active); everyone else's sales land
                // 91-360 days back so they read as genuinely lost.
                $isRecent = $index % 3 === 0 && $i === $saleCount - 1;
                $daysAgo = $isRecent ? random_int(1, 30) : random_int(91, 360);

                $this->createBackdatedSale($customer, $branches->random(), $sellers->random(), now()->subDays($daysAgo));
            }
        }

        $this->command->info('Seeded historical sales for ~65% of customers (mix of active and lost purchase histories).');
    }

    private function createBackdatedSale(Customer $customer, Branch $branch, User $seller, Carbon $date): void
    {
        $items = $this->pickAvailableItems($branch);

        if (empty($items)) {
            return;
        }

        try {
            $sale = $this->saleService->create([
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'user_id' => $seller->id,
                'items' => $items,
            ]);
        } catch (InsufficientStockException) {
            return;
        }

        // SaleService always stamps sale_date as now() — backdate it after
        // the fact so seeded purchase history spreads realistically over
        // time. The invoice_number keeps today's date; that's cosmetic only
        // and doesn't affect anything scopeLost()/KPI/reporting reads.
        DB::table('sales')->where('id', $sale->id)->update([
            'sale_date' => $date,
            'created_at' => $date,
            'updated_at' => $date,
        ]);
    }
}
