<?php

namespace App\Services;

use App\Enums\SaleStatus;
use App\Events\SaleCompleted;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleService
{
    /**
     * Create a sale atomically: lock and check stock for every line item
     * before deducting anything, so an insufficient-stock line rolls back
     * the whole sale rather than leaving a partial deduction behind.
     *
     * @param  array{customer_id: int, branch_id: int, user_id: int, items: array<int, array{product_id: int, quantity: int}>}  $data
     *
     * @throws InsufficientStockException
     */
    public function create(array $data): Sale
    {
        $sale = DB::transaction(function () use ($data) {
            $sale = Sale::create([
                'customer_id' => $data['customer_id'],
                'branch_id' => $data['branch_id'],
                'user_id' => $data['user_id'],
                // Temporary placeholder — replaced below with a value
                // derived from the sale's own id, which is only known
                // once the row exists.
                'invoice_number' => (string) Str::uuid(),
                'total_amount' => 0,
                'status' => SaleStatus::Completed,
                'sale_date' => now(),
            ]);

            $total = 0;

            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                $stock = ProductStock::query()
                    ->where('product_id', $product->id)
                    ->where('branch_id', $data['branch_id'])
                    ->lockForUpdate()
                    ->first();

                $available = $stock?->quantity ?? 0;

                if ($available < $item['quantity']) {
                    throw InsufficientStockException::forProduct($product, $item['quantity'], $available);
                }

                $stock->decrement('quantity', $item['quantity']);

                $unitPrice = $product->price;
                $subtotal = $unitPrice * $item['quantity'];
                $total += $subtotal;

                $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);
            }

            $sale->update([
                // Derived from the sale's own auto-increment id, which is
                // globally unique the moment the row is created — no
                // separate sequence table or race-prone counter needed.
                'invoice_number' => sprintf('INV-B%d-%s-%04d', $sale->branch_id, $sale->sale_date->format('Ymd'), $sale->id),
                'total_amount' => $total,
            ]);

            return $sale;
        });

        SaleCompleted::dispatch($sale);

        return $sale;
    }

    /**
     * Render the invoice PDF for a sale on demand, reusing the same view as
     * the queued GenerateInvoicePdfJob so download/print never have to wait
     * on that job's queue (and stay identical in layout to the emailed copy).
     */
    public function renderInvoicePdf(Sale $sale): PdfDocument
    {
        $sale->loadMissing(['branch', 'customer', 'items.product']);

        return Pdf::loadView('invoices.pdf', ['sale' => $sale]);
    }
}
