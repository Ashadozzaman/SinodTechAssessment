<?php

namespace App\Listeners;

use App\Events\SaleCompleted;

/**
 * No-op for now — filled in at Prompt 7 (Invoice Generation & Email).
 * Will dispatch SendInvoiceEmailJob, chained after the PDF job.
 */
class SendInvoiceEmailListener
{
    public function handle(SaleCompleted $event): void {}
}
