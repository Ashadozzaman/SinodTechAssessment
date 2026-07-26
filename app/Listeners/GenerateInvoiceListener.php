<?php

namespace App\Listeners;

use App\Events\SaleCompleted;

/**
 * No-op for now — filled in at Prompt 7 (Invoice Generation & Email).
 * Will dispatch GenerateInvoicePdfJob for the completed sale.
 */
class GenerateInvoiceListener
{
    public function handle(SaleCompleted $event): void {}
}
