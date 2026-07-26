<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Dhaka Central Branch',
                'address' => '12 Gulshan Avenue, Dhaka 1212',
                'phone' => '+880-2-9887001',
                'is_active' => true,
            ],
            [
                'name' => 'Chattogram Port Branch',
                'address' => '45 Agrabad Commercial Area, Chattogram 4100',
                'phone' => '+880-31-2510022',
                'is_active' => true,
            ],
            [
                'name' => 'Sylhet Branch',
                'address' => '8 Zindabazar Road, Sylhet 3100',
                'phone' => '+880-821-728833',
                'is_active' => true,
            ],
        ];

        foreach ($branches as $branch) {
            Branch::firstOrCreate(['name' => $branch['name']], $branch);
        }
    }
}
