<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        collect(['roles.view', 'roles.create', 'roles.update', 'roles.delete'])
            ->each(fn (string $permission) => Permission::create(['name' => $permission]));
    }

    protected function userWithPermission(string $permission): User
    {
        $role = Role::create(['name' => $permission . '-role']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/roles')->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_view_index(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/roles')->assertForbidden();
    }

    public function test_user_with_permission_can_view_index(): void
    {
        $this->actingAs($this->userWithPermission('roles.view'));

        $this->get('/roles')->assertOk();
    }

    public function test_user_with_permission_can_create_role_with_permissions(): void
    {
        $this->actingAs($this->userWithPermission('roles.create'));

        $response = $this->post('/roles', [
            'name' => 'Manager',
            'permissions' => ['roles.view'],
        ]);

        $response->assertRedirect(route('roles.index'));

        $role = Role::where('name', 'Manager')->first();
        $this->assertNotNull($role);
        $this->assertTrue($role->hasPermissionTo('roles.view'));
    }

    public function test_user_without_create_permission_cannot_store_role(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/roles', [
            'name' => 'Manager',
            'permissions' => ['roles.view'],
        ])->assertForbidden();

        $this->assertDatabaseMissing('roles', ['name' => 'Manager']);
    }

    public function test_user_with_permission_can_update_role(): void
    {
        $actor = $this->userWithPermission('roles.update');
        $role = Role::create(['name' => 'Manager']);

        $response = $this->actingAs($actor)->put("/roles/{$role->id}", [
            'name' => 'Senior Manager',
            'permissions' => ['roles.view'],
        ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertSame('Senior Manager', $role->fresh()->name);
        $this->assertTrue($role->fresh()->hasPermissionTo('roles.view'));
    }

    public function test_user_with_permission_can_delete_role(): void
    {
        $actor = $this->userWithPermission('roles.delete');
        $role = Role::create(['name' => 'Manager']);

        $response = $this->actingAs($actor)->delete("/roles/{$role->id}");

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_user_without_delete_permission_cannot_delete_role(): void
    {
        $actor = $this->userWithPermission('roles.view');
        $role = Role::create(['name' => 'Manager']);

        $this->actingAs($actor)->delete("/roles/{$role->id}")->assertForbidden();

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }
}
