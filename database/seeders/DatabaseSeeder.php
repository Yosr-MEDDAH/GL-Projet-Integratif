<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles table
        $this->call(RolesSeeder::class);

        // Create users with different attributes
        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('12345678'), // Set password to "12345678"
            'phone' => '1234567890',
            'image' => 'john.jpg',
            'isActive' => true,
            'code_2FA' => 123456, // Example code for 2FA
            'isEnable' => false,
            'role_id' => 2, // Assign a role ID according to your roles setup
            'idErp' => 1, // Assuming these are required fields
            'idFiscale' => 1,
            'adress' => '123 Street, City',
            'nationnalites' => 'Nationality',
            'direction' => 'Direction',
        ]);


        User::factory()->create([
            'name' => 'Test Name',
            'email' => 'test@test.com',
            'password' => Hash::make('12345678'), // Set password to "12345678"
            'phone' => '1234567890',
            'image' => 'john.jpg',
            'isActive' => true,
            'code_2FA' => 123456, // Example code for 2FA
            'isEnable' => false,
            'role_id' => 2, // Assign a role ID according to your roles setup
            'idErp' => 1, // Assuming these are required fields
            'idFiscale' => 1,
            'adress' => '123 Street, City',
            'nationnalites' => 'Nationality',
            'direction' => 'Direction',
        ]);


    }
}
