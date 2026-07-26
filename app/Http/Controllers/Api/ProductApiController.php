<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductApiResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductApiController extends Controller
{
    /**
     * List products with their available stock.
     *
     * Supports `?branch_id=` to scope `available_stock` to a single branch
     * instead of summing across all branches.
     */
    public function index(Request $request)
    {
        $branchId = $request->integer('branch_id') ?: null;

        $products = Product::query()
            ->withSum(['stocks as available_stock' => function ($query) use ($branchId) {
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
            }], 'quantity')
            ->paginate(15)
            ->withQueryString();

        return ProductApiResource::collection($products);
    }
}
