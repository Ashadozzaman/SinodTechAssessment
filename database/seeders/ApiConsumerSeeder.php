<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApiConsumerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $consumer = User::firstOrCreate(
            ['email' => 'api-consumer@example.com'],
            ['name' => 'E-Commerce API Consumer', 'password' => Str::random(32)]
        );

        $consumer->tokens()->delete();

        $token = $consumer->createToken('ecommerce-integration', ['products:read']);

        $this->command->info('API consumer token (products:read): '.$token->plainTextToken);
    }
}
