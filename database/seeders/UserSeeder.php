<?php
namespace Database\Seeders;

use App\Models\User;
use App\Models\Address;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name'  => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        Address::create([
            'user_id'     => $admin->id,
            'label'       => 'Office',
            'line1'       => '123 Admin Street',
            'city'        => 'Dhaka',
            'state'       => 'Dhaka Division',
            'postal_code' => '1200',
            'country'     => 'BD',
            'is_default'  => true,
        ]);

        // Create a known customer account
        $customer = User::factory()->create([
            'name'  => 'Test Customer',
            'email' => 'customer@example.com',
        ]);

        Address::create([
            'user_id'     => $customer->id,
            'label'       => 'Home',
            'line1'       => '456 Customer Lane',
            'city'        => 'Chittagong',
            'state'       => 'Chittagong Division',
            'postal_code' => '4000',
            'country'     => 'BD',
            'is_default'  => true,
        ]);

        // Create 10 more random customers
        User::factory(10)->create()->each(function ($user) {
            Address::create([
                'user_id'     => $user->id,
                'label'       => 'Home',
                'line1'       => fake()->streetAddress(),
                'city'        => fake()->city(),
                'postal_code' => fake()->postcode(),
                'country'     => 'BD',
                'is_default'  => true,
            ]);
        });

        $this->command->info('✅ Users seeded');
    }
}