<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Database\Seeder;

class ProductStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds a realistic stock quantity for every product/branch combination.
     */
    public function run(): void
    {
        $branches = Branch::all();

        Product::all()->each(function (Product $product) use ($branches) {
            $branches->each(function (Branch $branch) use ($product) {
                ProductStock::firstOrCreate(
                    ['product_id' => $product->id, 'branch_id' => $branch->id],
                    ['quantity' => random_int(0, 150)]
                );
            });
        });
    }
}
