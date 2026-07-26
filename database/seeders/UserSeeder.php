<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seeds the Admin plus a Manager and two Employees per branch, so
     * later seeders (sales, assignments) have real actors to attach to
     * and the app is immediately explorable under every role.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => Hash::make('password')]
        );

        if (! $admin->hasRole('Admin')) {
            $admin->assignRole('Admin');
        }

        Branch::all()->each(function (Branch $branch) {
            $slug = str($branch->name)->before(' ')->lower();

            $manager = User::firstOrCreate(
                ['email' => "manager.{$slug}@example.com"],
                [
                    'name' => "{$branch->name} Manager",
                    'password' => Hash::make('password'),
                    'branch_id' => $branch->id,
                ]
            );

            if (! $manager->hasRole('Manager')) {
                $manager->assignRole('Manager');
            }

            foreach (['1', '2'] as $suffix) {
                $employee = User::firstOrCreate(
                    ['email' => "employee{$suffix}.{$slug}@example.com"],
                    [
                        'name' => "{$branch->name} Employee {$suffix}",
                        'password' => Hash::make('password'),
                        'branch_id' => $branch->id,
                    ]
                );

                if (! $employee->hasRole('Employee')) {
                    $employee->assignRole('Employee');
                }
            }
        });

        $this->command->info('Seeded users — Admin, one Manager and two Employees per branch. Password for all: "password".');
    }
}
