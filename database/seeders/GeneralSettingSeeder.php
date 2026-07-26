<?php

namespace Database\Seeders;

use App\Models\GeneralSetting;
use Illuminate\Database\Seeder;

class GeneralSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GeneralSetting::current();
    }
}
