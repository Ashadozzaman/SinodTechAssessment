<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjustStockRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Services\InventoryService;
use InvalidArgumentException;

class InventoryController extends Controller
{
    public function __construct(private readonly InventoryService $inventoryService) {}

    /**
     * Apply a manual stock adjustment for a product at a branch.
     */
    public function update(AdjustStockRequest $request, Product $product)
    {
        $branch = Branch::findOrFail($request->validated('branch_id'));

        try {
            $this->inventoryService->adjustStock($product, $branch, (int) $request->validated('delta'));
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['delta' => $e->getMessage()]);
        }

        return back()->with('success', 'Stock updated successfully.');
    }
}
