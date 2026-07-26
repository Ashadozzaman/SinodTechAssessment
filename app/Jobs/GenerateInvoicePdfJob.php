<?php

namespace App\Jobs;

use App\Models\Invoice;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class GenerateInvoicePdfJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Sale $sale) {}

    public function handle(): void
    {
        $this->sale->loadMissing(['branch', 'customer', 'items.product']);

        $pdf = Pdf::loadView('invoices.pdf', ['sale' => $this->sale]);
        $path = "invoices/{$this->sale->invoice_number}.pdf";

        Storage::disk('local')->put($path, $pdf->output());

        Invoice::updateOrCreate(
            ['sale_id' => $this->sale->id],
            ['invoice_number' => $this->sale->invoice_number, 'pdf_path' => $path],
        );
    }
}
