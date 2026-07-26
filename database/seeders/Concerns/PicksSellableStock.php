<?php

namespace Database\Seeders\Concerns;

use App\Models\Branch;
use App\Models\ProductStock;

/**
 * Shared by SaleSeeder and CustomerAssignmentSeeder — both need to pick a
 * few in-stock line items for a branch when fabricating a sale. Requiring
 * quantity >= 2 and leaving at least 1 unit behind means seeded sales never
 * deplete a product/branch pair to 0, so ProductStockSeeder's deliberate
 * near-zero-stock demo row stays untouched.
 */
trait PicksSellableStock
{
    /**
     * @return array<int, array{product_id: int, quantity: int}>
     */
    private function pickAvailableItems(Branch $branch, int $maxItems = 3): array
    {
        return ProductStock::query()
            ->where('branch_id', $branch->id)
            ->where('quantity', '>=', 2)
            ->inRandomOrder()
            ->limit(random_int(1, $maxItems))
            ->get()
            ->map(fn (ProductStock $stock) => [
                'product_id' => $stock->product_id,
                'quantity' => random_int(1, min(3, $stock->quantity - 1)),
            ])
            ->all();
    }
}
