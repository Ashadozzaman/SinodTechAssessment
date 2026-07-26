<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_expected_permissions(): void
    {
        $this->seed();

        $this->assertSame(29, Permission::count());
        $this->assertTrue(Permission::where('name', 'users.view')->exists());
        $this->assertTrue(Permission::where('name', 'roles.delete')->exists());
        $this->assertTrue(Permission::where('name', 'products.view')->exists());
        $this->assertTrue(Permission::where('name', 'products.delete')->exists());
        $this->assertTrue(Permission::where('name', 'branches.view')->exists());
        $this->assertTrue(Permission::where('name', 'branches.delete')->exists());
        $this->assertTrue(Permission::where('name', 'inventory.adjust')->exists());
        $this->assertTrue(Permission::where('name', 'customers.view')->exists());
        $this->assertTrue(Permission::where('name', 'customers.delete')->exists());
        $this->assertTrue(Permission::where('name', 'customers.lost')->exists());
        $this->assertTrue(Permission::where('name', 'customers.assign')->exists());
        $this->assertTrue(Permission::where('name', 'crm.reengage')->exists());
        $this->assertTrue(Permission::where('name', 'kpi.view')->exists());
        $this->assertTrue(Permission::where('name', 'sales.view')->exists());
        $this->assertTrue(Permission::where('name', 'sales.create')->exists());
        $this->assertTrue(Permission::where('name', 'sales.delete')->exists());
    }

    public function test_seeder_creates_admin_role_with_all_permissions(): void
    {
        $this->seed();

        $admin = Role::where('name', 'Admin')->first();

        $this->assertNotNull($admin);
        $this->assertSame(Permission::count(), $admin->permissions()->count());
    }

    public function test_seeder_creates_manager_role_with_full_product_management(): void
    {
        $this->seed();

        $manager = Role::where('name', 'Manager')->first();

        $this->assertNotNull($manager);
        $this->assertTrue($manager->hasPermissionTo('products.view'));
        $this->assertTrue($manager->hasPermissionTo('products.create'));
        $this->assertTrue($manager->hasPermissionTo('products.update'));
        $this->assertTrue($manager->hasPermissionTo('products.delete'));
        $this->assertTrue($manager->hasPermissionTo('branches.view'));
        $this->assertFalse($manager->hasPermissionTo('branches.create'));
        $this->assertTrue($manager->hasPermissionTo('inventory.adjust'));
        $this->assertTrue($manager->hasPermissionTo('customers.view'));
        $this->assertTrue($manager->hasPermissionTo('customers.create'));
        $this->assertTrue($manager->hasPermissionTo('customers.delete'));
        $this->assertTrue($manager->hasPermissionTo('customers.lost'));
        $this->assertFalse($manager->hasPermissionTo('customers.assign'));
        $this->assertTrue($manager->hasPermissionTo('crm.reengage'));
        $this->assertFalse($manager->hasPermissionTo('kpi.view'));
        $this->assertTrue($manager->hasPermissionTo('sales.view'));
        $this->assertTrue($manager->hasPermissionTo('sales.create'));
        $this->assertTrue($manager->hasPermissionTo('sales.delete'));
    }

    public function test_seeder_creates_employee_role_with_view_only_product_access(): void
    {
        $this->seed();

        $employee = Role::where('name', 'Employee')->first();

        $this->assertNotNull($employee);
        $this->assertTrue($employee->hasPermissionTo('products.view'));
        $this->assertFalse($employee->hasPermissionTo('products.create'));
        $this->assertFalse($employee->hasPermissionTo('products.update'));
        $this->assertFalse($employee->hasPermissionTo('products.delete'));
        $this->assertFalse($employee->hasPermissionTo('inventory.adjust'));
        $this->assertTrue($employee->hasPermissionTo('customers.view'));
        $this->assertFalse($employee->hasPermissionTo('customers.create'));
        $this->assertFalse($employee->hasPermissionTo('customers.lost'));
        $this->assertFalse($employee->hasPermissionTo('crm.reengage'));
        $this->assertFalse($employee->hasPermissionTo('kpi.view'));
        $this->assertTrue($employee->hasPermissionTo('sales.view'));
        $this->assertTrue($employee->hasPermissionTo('sales.create'));
        $this->assertFalse($employee->hasPermissionTo('sales.update'));
        $this->assertFalse($employee->hasPermissionTo('sales.delete'));
    }

    public function test_seeder_creates_admin_user_assigned_to_admin_role(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('Admin'));
        $this->assertTrue($admin->can('users.delete'));
    }
}
