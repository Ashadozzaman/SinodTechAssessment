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
                    ['quantity' => random_int(2, 150)]
                );
            });
        });

        // Guarantee one deterministic near-zero-stock product/branch pair so
        // the insufficient-stock rejection is demonstrable immediately after
        // seeding, without depending on random quantities landing on 0/1.
        // SaleSeeder deliberately never sells from stock below quantity 2,
        // so this row survives untouched by later seeders.
        $demoProduct = Product::orderBy('id')->first();
        $demoBranch = $branches->first();

        if ($demoProduct && $demoBranch) {
            ProductStock::updateOrCreate(
                ['product_id' => $demoProduct->id, 'branch_id' => $demoBranch->id],
                ['quantity' => 1]
            );

            $this->command->info("Seeded near-zero stock demo: \"{$demoProduct->name}\" at {$demoBranch->name} (qty: 1).");
        }
    }
}
