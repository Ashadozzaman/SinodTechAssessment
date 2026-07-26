<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Models\Customer;
use App\Models\CustomerAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        collect(['customers.view', 'customers.create', 'customers.update', 'customers.delete'])
            ->each(fn (string $permission) => Permission::create(['name' => $permission]));
    }

    protected function userWithPermission(string $permission): User
    {
        $role = Role::create(['name' => $permission.'-role']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/customers')->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_view_index(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/customers')->assertForbidden();
    }

    public function test_user_with_permission_can_view_index(): void
    {
        $this->actingAs($this->userWithPermission('customers.view'));

        $this->get('/customers')->assertOk();
    }

    public function test_user_with_permission_can_create_customer(): void
    {
        $this->actingAs($this->userWithPermission('customers.create'));

        $response = $this->post('/customers', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '01712345678',
            'address' => '123 Main St',
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', ['phone' => '01712345678', 'email' => 'jane@example.com']);
    }

    public function test_user_without_create_permission_cannot_store_customer(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/customers', [
            'name' => 'Jane Doe',
            'phone' => '01712345678',
        ])->assertForbidden();

        $this->assertDatabaseMissing('customers', ['phone' => '01712345678']);
    }

    public function test_duplicate_phone_is_rejected(): void
    {
        Customer::create(['name' => 'Existing', 'phone' => '01712345678']);

        $response = $this->actingAs($this->userWithPermission('customers.create'))->post('/customers', [
            'name' => 'Jane Doe',
            'phone' => '01712345678',
        ]);

        $response->assertSessionHasErrors('phone');
    }

    public function test_user_with_permission_can_update_customer(): void
    {
        $actor = $this->userWithPermission('customers.update');
        $customer = Customer::create(['name' => 'Old Name', 'phone' => '01711111111']);

        $response = $this->actingAs($actor)->put("/customers/{$customer->id}", [
            'name' => 'New Name',
            'phone' => '01711111111',
        ]);

        $response->assertRedirect(route('customers.index'));
        $this->assertSame('New Name', $customer->fresh()->name);
    }

    public function test_user_with_permission_can_delete_customer(): void
    {
        $actor = $this->userWithPermission('customers.delete');
        $customer = Customer::create(['name' => 'Delete Me', 'phone' => '01722222222']);

        $response = $this->actingAs($actor)->delete("/customers/{$customer->id}");

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_user_without_delete_permission_cannot_delete_customer(): void
    {
        $actor = $this->userWithPermission('customers.view');
        $customer = Customer::create(['name' => 'Keep Me', 'phone' => '01733333333']);

        $this->actingAs($actor)->delete("/customers/{$customer->id}")->assertForbidden();

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    }

    public function test_search_filters_customers_by_name_or_phone(): void
    {
        $actor = $this->userWithPermission('customers.view');
        $alice = Customer::create(['name' => 'Alice Wonderland', 'phone' => '01744444444']);
        Customer::create(['name' => 'Bob Builder', 'phone' => '01755555555']);

        // A view-only actor (Employee) only sees customers actively
        // assigned to them (Prompt 9 / CLAUDE.md §3a) — assign Alice so the
        // search-within-scope interaction is still exercised.
        CustomerAssignment::create([
            'customer_id' => $alice->id,
            'employee_id' => $actor->id,
            'assigned_by' => $actor->id,
            'status' => AssignmentStatus::Active,
            'assigned_at' => now(),
        ]);

        $response = $this->actingAs($actor)->get('/customers?search=Alice');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Alice Wonderland')
        );
    }
}
