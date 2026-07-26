<?php

namespace App\Services\Channels;

use App\Contracts\ReengagementChannel;
use App\Jobs\SendReengagementEmailJob;
use App\Models\Customer;
use RuntimeException;

/**
 * REAL delivery — dispatches a queued Job (SendReengagementEmailJob) that
 * sends via the project's Mailtrap SMTP setup (ARCHITECTURE.md §5.4). This
 * is the "real" half of the email/SMS split; see SmsReengagementChannel for
 * the simulated half.
 */
class EmailReengagementChannel implements ReengagementChannel
{
    public function send(Customer $customer, string $message): void
    {
        if (! $customer->email) {
            // Thrown synchronously (before anything is queued) so
            // CrmService can record this attempt as `failed` instead of
            // optimistically marking it `sent`.
            throw new RuntimeException("Cannot send re-engagement email: {$customer->name} has no email on file.");
        }

        SendReengagementEmailJob::dispatch($customer, $message);
    }
}
