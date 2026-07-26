<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleController extends Controller
{
    public function __construct(private readonly SaleService $saleService) {}

    /**
     * Display a listing of the resource.
     *
     * Supports `search` (matches invoice number/customer name), `branch_id`,
     * and `status`.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'branch_id', 'status']);

        $sales = $this->filteredSales($filters)
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Sales/Index', [
            'sales' => SaleResource::collection($sales)->response()->getData(true),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    /**
     * Shared filtered query for the index listing and the CSV export, so
     * "export" always reflects exactly what's currently on screen
     * (same search/branch/status params, same sort).
     */
    private function filteredSales(array $filters)
    {
        return Sale::with(['customer', 'branch', 'cashier'])
            ->search($filters['search'] ?? null)
            ->when($filters['branch_id'] ?? null, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('sale_date');
    }

    /**
     * Stream the currently filtered sales list as a CSV file (opens
     * directly in Excel) — respects the same search/branch/status query
     * params as the index listing.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->only(['search', 'branch_id', 'status']);
        $sales = $this->filteredSales($filters)->get();

        $filename = 'sales-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($sales) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Invoice #', 'Customer', 'Branch', 'Cashier', 'Status', 'Date', 'Total']);

            foreach ($sales as $sale) {
                fputcsv($handle, [
                    $sale->invoice_number,
                    $sale->customer?->name ?? '—',
                    $sale->branch?->name ?? '—',
                    $sale->cashier?->name ?? '—',
                    $sale->status->label(),
                    $sale->sale_date->format('Y-m-d H:i'),
                    $sale->total_amount,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Show the POS form for creating a new sale.
     */
    public function create()
    {
        return Inertia::render('Sales/CreateSale', [
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSaleRequest $request)
    {
        try {
            $sale = $this->saleService->create([
                ...$request->validated(),
                'user_id' => $request->user()->id,
            ]);
        } catch (InsufficientStockException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }

        return to_route('sales.show', $sale)->with('success', "Sale {$sale->invoice_number} completed.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale)
    {
        $sale->load(['customer', 'branch', 'cashier', 'items.product']);

        return Inertia::render('Sales/Show', [
            'sale' => SaleResource::make($sale),
        ]);
    }

    /**
     * Force-download the invoice PDF. Rendered on demand rather than reading
     * the queued GenerateInvoicePdfJob's stored copy, so it works even if
     * that job hasn't run yet.
     */
    public function downloadInvoice(Sale $sale)
    {
        return $this->saleService->renderInvoicePdf($sale)->download("{$sale->invoice_number}.pdf");
    }

    /**
     * Render a narrow, thermal-receipt-formatted view (80mm width) and
     * auto-trigger the browser print dialog — a full A4 PDF view is unusable
     * on a thermal receipt printer, so this is a separate layout from the
     * downloadable invoice PDF, not a reuse of it.
     */
    public function printInvoice(Sale $sale)
    {
        $sale->loadMissing(['branch', 'customer', 'items.product']);

        return view('invoices.thermal', ['sale' => $sale]);
    }

    /**
     * Lightweight customer lookup for the POS picker. Limited to 10 matches
     * — never dumps the full customers table (CLAUDE.md §2.6).
     */
    public function searchCustomers(Request $request)
    {
        $customers = Customer::query()
            ->search($request->string('search')->toString() ?: null)
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'phone', 'email']);

        return response()->json($customers);
    }

    /**
     * Lightweight product lookup for the POS picker, scoped to a branch so
     * the returned available_stock reflects what can actually be sold
     * there. Limited to 10 matches — never dumps the full products table.
     */
    public function searchProducts(Request $request)
    {
        $branchId = $request->integer('branch_id');

        $products = Product::query()
            ->where('is_active', true)
            ->search($request->string('search')->toString() ?: null)
            ->with(['stocks' => fn ($query) => $query->where('branch_id', $branchId)])
            ->limit(10)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'available_stock' => $product->stocks->first()->quantity ?? 0,
            ]);

        return response()->json($products);
    }
}
