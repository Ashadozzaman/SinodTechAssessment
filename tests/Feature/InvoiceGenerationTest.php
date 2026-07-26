<?php

namespace Tests\Feature;

use App\Events\SaleCompleted;
use App\Jobs\GenerateInvoicePdfJob;
use App\Jobs\SendInvoiceEmailJob;
use App\Listeners\GenerateInvoiceListener;
use App\Mail\InvoiceMail;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceGenerationTest extends TestCase
{
    use RefreshDatabase;

    private function createSale(Customer $customer): Sale
    {
        $branch = Branch::create(['name' => 'Main', 'address' => '1 Main St', 'phone' => '0000000000']);
        $cashier = User::factory()->create();
        $product = Product::create(['name' => 'Widget', 'sku' => 'WID-001', 'price' => 10.00]);
        ProductStock::create(['product_id' => $product->id, 'branch_id' => $branch->id, 'quantity' => 10]);

        return app(SaleService::class)->create([
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'user_id' => $cashier->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ]);
    }

    public function test_sale_completed_dispatches_pdf_job_chained_with_email_job(): void
    {
        Bus::fake();

        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000000', 'email' => 'jane@example.com']);
        $sale = $this->createSale($customer);

        (new GenerateInvoiceListener)->handle(new SaleCompleted($sale));

        Bus::assertChained([
            GenerateInvoicePdfJob::class,
            SendInvoiceEmailJob::class,
        ]);
    }

    public function test_generate_invoice_pdf_job_creates_invoice_row_with_pdf_path(): void
    {
        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000000', 'email' => 'jane@example.com']);
        $sale = $this->createSale($customer);

        (new GenerateInvoicePdfJob($sale))->handle();

        $this->assertDatabaseHas('invoices', [
            'sale_id' => $sale->id,
            'invoice_number' => $sale->invoice_number,
        ]);

        $invoice = Invoice::where('sale_id', $sale->id)->first();
        $this->assertNotNull($invoice->pdf_path);
        $this->assertTrue(Storage::disk('local')->exists($invoice->pdf_path));
    }

    public function test_send_invoice_email_job_sends_mail_with_attachment_and_sets_emailed_at(): void
    {
        Mail::fake();

        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000000', 'email' => 'jane@example.com']);
        $sale = $this->createSale($customer);

        (new GenerateInvoicePdfJob($sale))->handle();
        (new SendInvoiceEmailJob($sale))->handle();

        Mail::assertSent(InvoiceMail::class, function (InvoiceMail $mail) use ($sale) {
            return $mail->sale->id === $sale->id && count($mail->attachments()) === 1;
        });

        $invoice = Invoice::where('sale_id', $sale->id)->first();
        $this->assertNotNull($invoice->emailed_at);
    }

    public function test_send_invoice_email_job_skips_gracefully_when_customer_has_no_email(): void
    {
        Mail::fake();

        $customer = Customer::create(['name' => 'No Email Customer', 'phone' => '01711111111']);
        $sale = $this->createSale($customer);

        (new GenerateInvoicePdfJob($sale))->handle();
        (new SendInvoiceEmailJob($sale))->handle();

        Mail::assertNothingSent();

        $invoice = Invoice::where('sale_id', $sale->id)->first();
        $this->assertNull($invoice->emailed_at);
    }
}
