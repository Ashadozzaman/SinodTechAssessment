<?php

namespace Database\Seeders;

use App\Exceptions\DuplicateActiveAssignmentException;
use App\Exceptions\InsufficientStockException;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use App\Services\CrmService;
use App\Services\SaleService;
use Database\Seeders\Concerns\PicksSellableStock;
use Illuminate\Database\Seeder;

class CustomerAssignmentSeeder extends Seeder
{
    use PicksSellableStock;

    public function __construct(
        private readonly CrmService $crmService,
        private readonly SaleService $saleService,
    ) {}

    /**
     * Run the database seeds.
     *
     * Assigns a batch of currently-lost customers to employees via
     * CrmService::assignCustomer(), then completes a follow-up sale for
     * about half of them through SaleService::create(). That sale fires
     * SaleCompleted exactly like a real one, so AwardEmployeeKpiListener
     * awards KPI points and resolves the assignment through the real
     * production path — not a manually-poked status column. The rest stay
     * 'active' so the Lost Customers page also shows pending assignments.
     */
    public function run(): void
    {
        $admin = User::role('Admin')->first();
        $employees = User::role('Employee')->get();
        $branches = Branch::all();

        if (! $admin || $employees->isEmpty() || $branches->isEmpty()) {
            return;
        }

        $lostCustomers = Customer::lost()->inRandomOrder()->limit(8)->get();
        $resolved = 0;

        foreach ($lostCustomers as $index => $customer) {
            $employee = $employees->random();

            try {
                $this->crmService->assignCustomer($customer, $employee, $admin);
            } catch (DuplicateActiveAssignmentException) {
                continue;
            }

            if ($index % 2 === 0) {
                $branch = $employee->branch ?? $branches->random();

                if ($this->completeFollowUpSale($customer, $branch, $employee)) {
                    $resolved++;
                }
            }
        }

        $this->command->info("Seeded {$lostCustomers->count()} customer assignments ({$resolved} resolved with a completed follow-up sale, rest active).");
    }

    private function completeFollowUpSale(Customer $customer, Branch $branch, User $employee): bool
    {
        $items = $this->pickAvailableItems($branch);

        if (empty($items)) {
            return false;
        }

        try {
            $this->saleService->create([
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'user_id' => $employee->id,
                'items' => $items,
            ]);
        } catch (InsufficientStockException) {
            return false;
        }

        return true;
    }
}
