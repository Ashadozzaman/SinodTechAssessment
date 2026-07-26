<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Spreads created_at over the last 12 months so Prompt 8's lost-customer
     * scope has genuinely old customers to detect.
     */
    public function run(): void
    {
        $faker = fake();

        for ($i = 0; $i < 45; $i++) {
            $createdAt = fake()->dateTimeBetween('-12 months', 'now');

            $customer = Customer::create([
                'name' => $faker->name(),
                'email' => $faker->boolean(80) ? $faker->unique()->safeEmail() : null,
                'phone' => $faker->unique()->numerify('01#########'),
                'address' => $faker->address(),
            ]);

            DB::table('customers')
                ->where('id', $customer->id)
                ->update(['created_at' => $createdAt, 'updated_at' => $createdAt]);
        }
    }
}
