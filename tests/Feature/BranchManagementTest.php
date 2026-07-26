<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Branches has no dedicated destroy-permission test elsewhere, and its
 * "Manager is view-only" carve-out (CLAUDE.md §3a role table) is easy to
 * regress silently, so these boundaries are asserted against the real
 * seeded roles rather than an ad-hoc permission.
 */
class BranchManagementTest extends TestCase
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

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/branches')->assertRedirect('/login');
    }

    public function test_employee_can_view_but_not_manage_branches(): void
    {
        $employee = $this->userWithRole('Employee');

        $this->actingAs($employee)->get('/branches')->assertOk();
        $this->actingAs($employee)->get('/branches/create')->assertForbidden();
        $this->actingAs($employee)->post('/branches', ['name' => 'X', 'address' => 'Y', 'phone' => '1'])->assertForbidden();
    }

    public function test_manager_can_view_but_not_manage_branches(): void
    {
        $manager = $this->userWithRole('Manager');
        $branch = Branch::create(['name' => 'Main', 'address' => '1 Main St', 'phone' => '0000000000']);

        $this->actingAs($manager)->get('/branches')->assertOk();
        $this->actingAs($manager)->get('/branches/create')->assertForbidden();
        $this->actingAs($manager)->post('/branches', ['name' => 'X', 'address' => 'Y', 'phone' => '1'])->assertForbidden();
        $this->actingAs($manager)->put("/branches/{$branch->id}", ['name' => 'X', 'address' => 'Y', 'phone' => '1'])->assertForbidden();
        $this->actingAs($manager)->delete("/branches/{$branch->id}")->assertForbidden();

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'name' => 'Main']);
    }

    public function test_admin_has_full_branch_management_access(): void
    {
        $admin = $this->userWithRole('Admin');

        $this->actingAs($admin)->get('/branches')->assertOk();

        $createResponse = $this->actingAs($admin)->post('/branches', [
            'name' => 'New Branch',
            'address' => '99 New St',
            'phone' => '0111111111',
        ]);
        $createResponse->assertRedirect(route('branches.index'));
        $branch = Branch::where('name', 'New Branch')->firstOrFail();

        $this->actingAs($admin)
            ->put("/branches/{$branch->id}", ['name' => 'Renamed Branch', 'address' => '99 New St', 'phone' => '0111111111'])
            ->assertRedirect(route('branches.index'));
        $this->assertSame('Renamed Branch', $branch->fresh()->name);

        $this->actingAs($admin)->delete("/branches/{$branch->id}")->assertRedirect(route('branches.index'));
        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
    }
}
