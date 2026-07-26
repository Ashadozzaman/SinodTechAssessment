<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedProductWithStock(): Product
    {
        $category = Category::create(['name' => 'Electronics', 'slug' => 'electronics']);

        $product = Product::create([
            'name' => 'Wireless Mouse',
            'sku' => 'ELEC-0001',
            'category_id' => $category->id,
            'price' => 19.99,
            'is_active' => true,
        ]);

        $branchOne = Branch::create(['name' => 'Main Branch', 'address' => 'Dhaka', 'phone' => '01700000001', 'is_active' => true]);
        $branchTwo = Branch::create(['name' => 'Second Branch', 'address' => 'Chattogram', 'phone' => '01700000002', 'is_active' => true]);

        ProductStock::create(['product_id' => $product->id, 'branch_id' => $branchOne->id, 'quantity' => 10]);
        ProductStock::create(['product_id' => $product->id, 'branch_id' => $branchTwo->id, 'quantity' => 5]);

        return $product;
    }

    public function test_a_valid_token_with_products_read_ability_returns_products(): void
    {
        $this->seedProductWithStock();

        $consumer = User::factory()->create();
        $token = $consumer->createToken('test-consumer', ['products:read'])->plainTextToken;

        $response = $this->getJson('/api/v1/products', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.0', [
            'sku' => 'ELEC-0001',
            'name' => 'Wireless Mouse',
            'price' => '19.99',
            'available_stock' => 15,
        ]);
    }

    public function test_a_branch_id_filter_scopes_available_stock_to_that_branch(): void
    {
        $product = $this->seedProductWithStock();
        $branchId = $product->stocks()->where('quantity', 10)->first()->branch_id;

        $consumer = User::factory()->create();
        $token = $consumer->createToken('test-consumer', ['products:read'])->plainTextToken;

        $response = $this->getJson("/api/v1/products?branch_id={$branchId}", [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.0.available_stock', 10);
    }

    public function test_a_request_without_a_token_is_unauthenticated(): void
    {
        $this->seedProductWithStock();

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(401);
    }

    public function test_a_token_without_the_products_read_ability_is_forbidden(): void
    {
        $this->seedProductWithStock();

        $consumer = User::factory()->create();
        $token = $consumer->createToken('test-consumer', ['other:ability'])->plainTextToken;

        $response = $this->getJson('/api/v1/products', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(403);
    }
}
