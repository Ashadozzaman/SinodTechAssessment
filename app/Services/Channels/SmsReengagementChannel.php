<?php

namespace App\Services\Channels;

use App\Contracts\ReengagementChannel;
use App\Models\Customer;
use Illuminate\Support\Facades\Log;

/**
 * SIMULATED — there is no real SMS gateway wired up for this assessment
 * (ARCHITECTURE.md §5.4). Instead of calling a provider, this logs the
 * outgoing message; CrmService marks the resulting `customer_engagements`
 * row `status = simulated` so this never reads as an actual delivery. This
 * is a deliberate, documented simplification (see docs/COMPLETED_FEATURES.md),
 * not an oversight.
 */
class SmsReengagementChannel implements ReengagementChannel
{
    public function send(Customer $customer, string $message): void
    {
        Log::info("[SIMULATED SMS] To {$customer->phone}: {$message}");
    }
}
