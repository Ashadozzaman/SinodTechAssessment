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

        $sales = Sale::with(['customer', 'branch', 'cashier'])
            ->search($filters['search'] ?? null)
            ->when($filters['branch_id'] ?? null, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest('sale_date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Sales/Index', [
            'sales' => SaleResource::collection($sales)->response()->getData(true),
            'branches' => Branch::orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
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
