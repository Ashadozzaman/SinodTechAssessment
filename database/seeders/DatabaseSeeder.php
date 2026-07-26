<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Dependency order matters: each seeder below assumes everything above
     * it already exists (e.g. SaleSeeder needs customers/users/stock;
     * CustomerAssignmentSeeder needs lost customers, which only exist once
     * SaleSeeder has run).
     */
    public function run(): void
    {
        $this->call([
            GeneralSettingSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            BranchSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            ProductStockSeeder::class,
            CustomerSeeder::class,
            UserSeeder::class,
            SaleSeeder::class,
            CustomerAssignmentSeeder::class,
            CustomerEngagementSeeder::class,
            ApiConsumerSeeder::class,
        ]);
    }
}
