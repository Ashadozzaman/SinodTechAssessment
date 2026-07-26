<?php

namespace App\Listeners;

use App\Events\SaleCompleted;
use App\Jobs\GenerateInvoicePdfJob;
use App\Jobs\SendInvoiceEmailJob;

class GenerateInvoiceListener
{
    /**
     * Dispatches both invoice jobs from a single call site so they can be
     * chained (withChain requires one dispatch site) — this supersedes the
     * separate SendInvoiceEmailListener placeholder from Prompt 5, since two
     * independent listeners for the same event have no ordering guarantee
     * and can't safely chain between themselves.
     */
    public function handle(SaleCompleted $event): void
    {
        GenerateInvoicePdfJob::withChain([
            new SendInvoiceEmailJob($event->sale),
        ])->dispatch($event->sale);
    }
}
