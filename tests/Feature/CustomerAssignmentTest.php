<?php

namespace Tests\Feature;

use App\Enums\AssignmentStatus;
use App\Exceptions\DuplicateActiveAssignmentException;
use App\Models\Customer;
use App\Models\User;
use App\Services\CrmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private CrmService $crmService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->crmService = app(CrmService::class);

        collect(['customers.view', 'customers.assign'])
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

    private function employeeUser(): User
    {
        $role = Role::firstOrCreate(['name' => 'Employee']);
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_assigning_a_customer_creates_an_active_assignment(): void
    {
        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000010']);
        $admin = User::factory()->create();
        $employee = $this->employeeUser();

        $assignment = $this->crmService->assignCustomer($customer, $employee, $admin);

        $this->assertSame(AssignmentStatus::Active, $assignment->status);
        $this->assertSame($employee->id, $assignment->employee_id);
        $this->assertSame($admin->id, $assignment->assigned_by);
    }

    public function test_a_second_active_assignment_for_the_same_customer_is_rejected(): void
    {
        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000011']);
        $admin = User::factory()->create();
        $employeeOne = $this->employeeUser();
        $employeeTwo = $this->employeeUser();

        $this->crmService->assignCustomer($customer, $employeeOne, $admin);

        $this->expectException(DuplicateActiveAssignmentException::class);

        $this->crmService->assignCustomer($customer, $employeeTwo, $admin);
    }

    public function test_resolving_an_assignment_allows_a_new_active_assignment_to_be_created(): void
    {
        $customer = Customer::create(['name' => 'John Doe', 'phone' => '01700000012']);
        $admin = User::factory()->create();
        $employeeOne = $this->employeeUser();
        $employeeTwo = $this->employeeUser();

        $first = $this->crmService->assignCustomer($customer, $employeeOne, $admin);
        $first->update(['status' => AssignmentStatus::Resolved, 'resolved_at' => now()]);

        $second = $this->crmService->assignCustomer($customer, $employeeTwo, $admin);

        $this->assertSame(AssignmentStatus::Active, $second->status);
        $this->assertSame($employeeTwo->id, $second->employee_id);
    }

    public function test_user_without_customers_assign_permission_cannot_assign_a_customer(): void
    {
        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000013']);
        $employee = $this->employeeUser();

        $this->actingAs($this->userWithPermission('customers.view'));

        $this->post("/customers/{$customer->id}/assign", ['employee_id' => $employee->id])
            ->assertForbidden();

        $this->assertDatabaseMissing('customer_assignments', ['customer_id' => $customer->id]);
    }

    public function test_user_with_customers_assign_permission_can_assign_a_customer(): void
    {
        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000014']);
        $employee = $this->employeeUser();

        $this->actingAs($this->userWithPermission('customers.assign'));

        $this->post("/customers/{$customer->id}/assign", ['employee_id' => $employee->id])
            ->assertRedirect();

        $this->assertDatabaseHas('customer_assignments', [
            'customer_id' => $customer->id,
            'employee_id' => $employee->id,
            'status' => 'active',
        ]);
    }

    public function test_assigning_to_a_non_employee_user_is_rejected(): void
    {
        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000015']);
        $notEmployee = User::factory()->create();

        $this->actingAs($this->userWithPermission('customers.assign'));

        $this->post("/customers/{$customer->id}/assign", ['employee_id' => $notEmployee->id])
            ->assertSessionHasErrors('employee_id');

        $this->assertDatabaseMissing('customer_assignments', ['customer_id' => $customer->id]);
    }

    public function test_assigning_an_already_assigned_customer_returns_a_validation_style_error(): void
    {
        $customer = Customer::create(['name' => 'Jane Doe', 'phone' => '01700000016']);
        $admin = $this->userWithPermission('customers.assign');
        $employeeOne = $this->employeeUser();
        $employeeTwo = $this->employeeUser();

        $this->crmService->assignCustomer($customer, $employeeOne, $admin);

        $this->actingAs($admin)
            ->post("/customers/{$customer->id}/assign", ['employee_id' => $employeeTwo->id])
            ->assertSessionHasErrors('employee_id');

        $this->assertSame(1, $customer->assignments()->where('status', AssignmentStatus::Active)->count());
    }

    public function test_employee_only_sees_customers_actively_assigned_to_them(): void
    {
        $assignedCustomer = Customer::create(['name' => 'Assigned Customer', 'phone' => '01700000017']);
        Customer::create(['name' => 'Other Customer', 'phone' => '01700000018']);

        $admin = User::factory()->create();
        $employee = $this->employeeUser();
        $employee->givePermissionTo('customers.view');

        $this->crmService->assignCustomer($assignedCustomer, $employee, $admin);

        $response = $this->actingAs($employee)->get('/customers');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Assigned Customer')
        );
    }

    public function test_employee_cannot_view_a_customer_not_assigned_to_them(): void
    {
        $customer = Customer::create(['name' => 'Unassigned Customer', 'phone' => '01700000019']);

        $employee = $this->employeeUser();
        $employee->givePermissionTo('customers.view');

        $this->actingAs($employee)->get("/customers/{$customer->id}")->assertForbidden();
    }
}
