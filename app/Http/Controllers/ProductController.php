<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * Supports `search` (matches name/SKU) and `category_id`.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search', 'category_id']);

        $products = Product::with('category')
            ->search($filters['search'] ?? null)
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => ProductResource::collection($products)->response()->getData(true),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Products/CreateProduct', [
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        Product::create($request->validated());

        return to_route('products.index')->with('success', 'Product created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $product->load('stocks');

        return Inertia::render('Products/EditProduct', [
            'product' => ProductResource::make($product),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'stocks' => Branch::orderBy('name')->get(['id', 'name'])->map(fn (Branch $branch) => [
                'branch_id' => $branch->id,
                'branch_name' => $branch->name,
                'quantity' => $product->stocks->firstWhere('branch_id', $branch->id)?->quantity ?? 0,
            ]),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $product->update($request->validated());

        return to_route('products.index')->with('success', 'Product updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return to_route('products.index')->with('success', 'Product deleted successfully.');
    }
}
