<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * kpi.view is Admin-only (RoleSeeder grants it to no other role) — this was
 * previously untested, so Manager/Employee getting in unnoticed wouldn't
 * have failed any existing test.
 */
class KpiLeaderboardTest extends TestCase
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
        $this->get('/kpi-leaderboard')->assertRedirect('/login');
    }

    public function test_employee_cannot_view_the_kpi_leaderboard(): void
    {
        $this->actingAs($this->userWithRole('Employee'))
            ->get('/kpi-leaderboard')
            ->assertForbidden();
    }

    public function test_manager_cannot_view_the_kpi_leaderboard(): void
    {
        $this->actingAs($this->userWithRole('Manager'))
            ->get('/kpi-leaderboard')
            ->assertForbidden();
    }

    public function test_admin_can_view_the_kpi_leaderboard(): void
    {
        $this->actingAs($this->userWithRole('Admin'))
            ->get('/kpi-leaderboard')
            ->assertOk();
    }
}
