<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    /**
     * Current quantity of a product at a branch (0 if no stock row exists yet).
     */
    public function getStock(Product $product, Branch $branch): int
    {
        return ProductStock::query()
            ->where('product_id', $product->id)
            ->where('branch_id', $branch->id)
            ->value('quantity') ?? 0;
    }

    /**
     * Apply a manual stock adjustment (positive to restock, negative to correct/deduct).
     *
     * Locks the product_stocks row for the duration of the transaction so
     * concurrent adjustments to the same product/branch pair serialize
     * instead of racing on a stale read.
     */
    public function adjustStock(Product $product, Branch $branch, int $delta): void
    {
        DB::transaction(function () use ($product, $branch, $delta) {
            $stock = ProductStock::query()
                ->where('product_id', $product->id)
                ->where('branch_id', $branch->id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = ProductStock::create([
                    'product_id' => $product->id,
                    'branch_id' => $branch->id,
                    'quantity' => 0,
                ]);
            }

            $newQuantity = $stock->quantity + $delta;

            if ($newQuantity < 0) {
                throw new InvalidArgumentException(
                    "Adjustment of {$delta} would drop stock below zero (current quantity: {$stock->quantity})."
                );
            }

            $stock->update(['quantity' => $newQuantity]);
        });
    }
}
