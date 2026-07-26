<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FlagLostCustomersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:flag-lost-customers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Log the current lost-customer count (ARCHITECTURE.md §5.2 — status is never stored, always computed live off sales)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = config('crm.lost_customer_days');
        $count = Customer::lost()->count();

        $this->info("Lost customers (no sale in {$days}+ days): {$count}");

        Log::info('crm:flag-lost-customers', ['lost_customer_days' => $days, 'count' => $count]);

        return self::SUCCESS;
    }
}
