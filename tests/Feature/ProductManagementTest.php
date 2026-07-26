<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Products (web CRUD) has no permission-boundary test elsewhere; Employee
 * is view-only while Manager gets full CRUD (CLAUDE.md §3a role table),
 * asserted here against the real seeded roles.
 */
class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Widget',
            'sku' => 'WID-' . uniqid(),
            'price' => 9.99,
        ], $overrides);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/products')->assertRedirect('/login');
    }

    public function test_employee_can_view_but_not_manage_products(): void
    {
        $employee = $this->userWithRole('Employee');
        $product = Product::create($this->validPayload());

        $this->actingAs($employee)->get('/products')->assertOk();
        $this->actingAs($employee)->get('/products/create')->assertForbidden();
        $this->actingAs($employee)->post('/products', $this->validPayload())->assertForbidden();
        $this->actingAs($employee)->put("/products/{$product->id}", $this->validPayload())->assertForbidden();
        $this->actingAs($employee)->delete("/products/{$product->id}")->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_manager_has_full_product_management_access(): void
    {
        $manager = $this->userWithRole('Manager');

        $this->actingAs($manager)->get('/products')->assertOk();

        $createResponse = $this->actingAs($manager)->post('/products', $this->validPayload(['sku' => 'WID-MGR-1']));
        $createResponse->assertRedirect(route('products.index'));
        $product = Product::where('sku', 'WID-MGR-1')->firstOrFail();

        $this->actingAs($manager)
            ->put("/products/{$product->id}", $this->validPayload(['sku' => 'WID-MGR-1', 'name' => 'Renamed']))
            ->assertRedirect(route('products.index'));
        $this->assertSame('Renamed', $product->fresh()->name);

        $this->actingAs($manager)->delete("/products/{$product->id}")->assertRedirect(route('products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_admin_has_full_product_management_access(): void
    {
        $admin = $this->userWithRole('Admin');

        $this->actingAs($admin)->get('/products')->assertOk();

        $createResponse = $this->actingAs($admin)->post('/products', $this->validPayload(['sku' => 'WID-ADM-1']));
        $createResponse->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['sku' => 'WID-ADM-1']);
    }
}
