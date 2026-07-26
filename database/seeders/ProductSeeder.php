<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            'Electronics' => [
                ['name' => 'Wireless Mouse', 'sku' => 'ELEC-0001', 'price' => 19.99],
                ['name' => 'Mechanical Keyboard', 'sku' => 'ELEC-0002', 'price' => 59.50],
                ['name' => '27" LED Monitor', 'sku' => 'ELEC-0003', 'price' => 189.00],
                ['name' => 'USB-C Charging Cable', 'sku' => 'ELEC-0004', 'price' => 8.99],
                ['name' => 'Bluetooth Headphones', 'sku' => 'ELEC-0005', 'price' => 45.00],
            ],
            'Groceries' => [
                ['name' => 'Basmati Rice 5kg', 'sku' => 'GROC-0001', 'price' => 12.50],
                ['name' => 'Sunflower Oil 1L', 'sku' => 'GROC-0002', 'price' => 4.25],
                ['name' => 'Red Lentils 1kg', 'sku' => 'GROC-0003', 'price' => 3.10],
                ['name' => 'Green Tea Box (25 bags)', 'sku' => 'GROC-0004', 'price' => 2.99],
                ['name' => 'Powdered Milk 900g', 'sku' => 'GROC-0005', 'price' => 7.80],
            ],
            'Home & Kitchen' => [
                ['name' => 'Non-Stick Frying Pan', 'sku' => 'HOME-0001', 'price' => 22.00],
                ['name' => 'Stainless Steel Cutlery Set', 'sku' => 'HOME-0002', 'price' => 34.99],
                ['name' => 'Electric Kettle 1.7L', 'sku' => 'HOME-0003', 'price' => 27.50],
                ['name' => 'Cotton Bedsheet Set', 'sku' => 'HOME-0004', 'price' => 18.75],
                ['name' => 'LED Desk Lamp', 'sku' => 'HOME-0005', 'price' => 14.20],
            ],
            'Apparel' => [
                ['name' => "Men's Cotton T-Shirt", 'sku' => 'APRL-0001', 'price' => 9.99],
                ['name' => "Women's Denim Jeans", 'sku' => 'APRL-0002', 'price' => 29.99],
                ['name' => 'Unisex Hoodie', 'sku' => 'APRL-0003', 'price' => 24.50],
                ['name' => 'Formal Leather Belt', 'sku' => 'APRL-0004', 'price' => 15.00],
                ['name' => 'Running Shoes', 'sku' => 'APRL-0005', 'price' => 39.90],
            ],
            'Stationery' => [
                ['name' => 'A4 Notebook (200 pages)', 'sku' => 'STAT-0001', 'price' => 2.50],
                ['name' => 'Ballpoint Pen (Pack of 10)', 'sku' => 'STAT-0002', 'price' => 3.75],
                ['name' => 'Sticky Notes Set', 'sku' => 'STAT-0003', 'price' => 1.99],
                ['name' => 'Desk Organizer', 'sku' => 'STAT-0004', 'price' => 11.40],
                ['name' => 'Highlighter Set (5 colors)', 'sku' => 'STAT-0005', 'price' => 4.60],
            ],
        ];

        foreach ($products as $categoryName => $items) {
            $category = Category::where('name', $categoryName)->first();

            foreach ($items as $item) {
                Product::firstOrCreate(['sku' => $item['sku']], [
                    'name' => $item['name'],
                    'sku' => $item['sku'],
                    'category_id' => $category?->id,
                    'price' => $item['price'],
                    'description' => null,
                    'is_active' => true,
                ]);
            }
        }
    }
}
