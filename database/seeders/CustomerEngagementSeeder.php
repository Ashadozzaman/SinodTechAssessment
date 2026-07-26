<?php

namespace Database\Seeders;

use App\Enums\EngagementChannel;
use App\Models\Customer;
use App\Models\User;
use App\Services\CrmService;
use Illuminate\Database\Seeder;

class CustomerEngagementSeeder extends Seeder
{
    public function __construct(private readonly CrmService $crmService) {}

    /**
     * Run the database seeds.
     *
     * Sends a re-engagement message to a batch of currently-lost customers
     * via the real CrmService::sendReengagement() — alternating email/sms
     * so both channels are represented, including the occasional 'failed'
     * email status for a customer with no email on file (a real outcome
     * CrmService already handles, not something this seeder fakes).
     */
    public function run(): void
    {
        $triggeredBy = User::role(['Admin', 'Manager'])->inRandomOrder()->first();
        $lostCustomers = Customer::lost()->inRandomOrder()->limit(10)->get();

        $messages = [
            EngagementChannel::Email->value => "We miss you! Here's 10% off your next purchase — come see what's new.",
            EngagementChannel::Sms->value => 'We miss you! Enjoy 10% off your next visit.',
        ];

        foreach ($lostCustomers as $index => $customer) {
            $channel = $index % 2 === 0 ? EngagementChannel::Email->value : EngagementChannel::Sms->value;

            $this->crmService->sendReengagement($customer, $channel, $messages[$channel], $triggeredBy);
        }

        $this->command->info("Seeded re-engagement attempts for {$lostCustomers->count()} lost customers (email + simulated sms).");
    }
}
