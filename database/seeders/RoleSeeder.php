<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->syncPermissions(Permission::all());

        $manager = Role::firstOrCreate(['name' => 'Manager']);
        $manager->syncPermissions([
            'products.view',
            'products.create',
            'products.update',
            'products.delete',
            'branches.view',
            'inventory.adjust',
            'customers.view',
            'customers.create',
            'customers.update',
            'customers.delete',
            'customers.lost',
            'crm.reengage',
            'sales.view',
            'sales.create',
            'sales.update',
            'sales.delete',
        ]);

        $employee = Role::firstOrCreate(['name' => 'Employee']);
        $employee->syncPermissions([
            'products.view',
            'branches.view',
            'customers.view',
            'sales.view',
            'sales.create',
        ]);
    }
}
