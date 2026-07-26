<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        collect(['users.view', 'users.create', 'users.update', 'users.delete'])
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
        $this->get('/users')->assertRedirect('/login');
    }

    public function test_user_without_permission_cannot_view_index(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/users')->assertForbidden();
    }

    public function test_user_with_permission_can_view_index(): void
    {
        $this->actingAs($this->userWithPermission('users.view'));

        $this->get('/users')->assertOk();
    }

    public function test_user_with_permission_can_create_user_with_roles(): void
    {
        $this->actingAs($this->userWithPermission('users.create'));
        Role::create(['name' => 'Employee']);

        $response = $this->post('/users', [
            'name' => 'New Employee',
            'email' => 'employee@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => ['Employee'],
        ]);

        $response->assertRedirect(route('users.index'));

        $user = User::where('email', 'employee@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('Employee'));
    }

    public function test_user_without_create_permission_cannot_store_user(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/users', [
            'name' => 'New Employee',
            'email' => 'employee@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'employee@example.com']);
    }

    public function test_user_with_permission_can_update_user(): void
    {
        $actor = $this->userWithPermission('users.update');
        $target = User::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($actor)->put("/users/{$target->id}", [
            'name' => 'Updated Name',
            'email' => $target->email,
            'roles' => [],
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertSame('Updated Name', $target->fresh()->name);
    }

    public function test_user_with_permission_can_delete_user(): void
    {
        $actor = $this->userWithPermission('users.delete');
        $target = User::factory()->create();

        $response = $this->actingAs($actor)->delete("/users/{$target->id}");

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_user_without_delete_permission_cannot_delete_user(): void
    {
        $actor = $this->userWithPermission('users.view');
        $target = User::factory()->create();

        $this->actingAs($actor)->delete("/users/{$target->id}")->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }
}
