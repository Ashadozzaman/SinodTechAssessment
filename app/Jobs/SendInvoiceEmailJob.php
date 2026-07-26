<?php

namespace App\Jobs;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\Sale;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Sale $sale) {}

    public function handle(): void
    {
        $this->sale->loadMissing('customer');

        // Runs after GenerateInvoicePdfJob in the chain dispatched by
        // GenerateInvoiceListener, so the Invoice row is guaranteed to exist.
        $invoice = Invoice::where('sale_id', $this->sale->id)->firstOrFail();

        if (! $this->sale->customer->email) {
            Log::notice("Skipping invoice email for sale {$this->sale->invoice_number}: customer has no email on file.");

            return;
        }

        Mail::to($this->sale->customer->email)->send(new InvoiceMail($this->sale, $invoice));

        $invoice->update(['emailed_at' => now()]);
    }
}
