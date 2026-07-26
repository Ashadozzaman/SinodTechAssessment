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

        $this->assertSame(8, Permission::count());
        $this->assertTrue(Permission::where('name', 'users.view')->exists());
        $this->assertTrue(Permission::where('name', 'roles.delete')->exists());
    }

    public function test_seeder_creates_admin_role_with_all_permissions(): void
    {
        $this->seed();

        $admin = Role::where('name', 'Admin')->first();

        $this->assertNotNull($admin);
        $this->assertSame(Permission::count(), $admin->permissions()->count());
    }

    public function test_seeder_creates_staff_role_with_no_permissions(): void
    {
        $this->seed();

        $staff = Role::where('name', 'Staff')->first();

        $this->assertNotNull($staff);
        $this->assertSame(0, $staff->permissions()->count());
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
