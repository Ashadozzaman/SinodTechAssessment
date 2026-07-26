<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            "users.view",
            "users.create",
            "users.update",
            "users.delete",
            "roles.view",
            "roles.create",
            "roles.update",
            "roles.delete",
            "products.view",
            "products.create",
            "products.update",
            "products.delete",
            "branches.view",
            "branches.create",
            "branches.update",
            "branches.delete",
            "inventory.adjust",
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
    }
}
