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


        User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john5000@example.com',
            'password' => Hash::make('12345678'),
            'phone' => '1234567890',
            'image' => 'john.jpg',
            'isActive' => true,
            'code_2FA' => 123456,
            'isTwoFactorEnabled' => false,
            'role_id' => 2,
            'idErp' => 1,
            'idFiscale' => 'smjdhf500',
            'adress' => '123 Street, City',
            'nationnalites' => 'Nationality',
            'direction' => 'Direction',
        ]);


        User::factory()->create([
            'name' => 'Anton Carey',
            'email' => 'test@test.com',
            'password' => Hash::make('12345678'),
            'phone' => '1234567890',
            'image' => 'john.jpg',
            'isActive' => true,
            'code_2FA' => 123456,
            'isTwoFactorEnabled' => false,
            'role_id' => 3,
            'idErp' => 1,
            'idFiscale' => 'jsdfsmlkdfdgddf12311',
            'adress' => '123 Street, City',
            'nationnalites' => 'Nationality',
            'direction' => 'Direction',
        ]);


        User::factory()->create([
            'name' => 'Benji Thomas',
            'email' => 'test2@test.com',
            'password' => Hash::make('12345678'),
            'phone' => '1234567890',
            'image' => 'john.jpg',
            'isActive' => true,
            'code_2FA' => 123456,
            'isTwoFactorEnabled' => false,
            'role_id' => 3,
            'idErp' => 1,
            'idFiscale' => '2sdqfklghf455',
            'adress' => '123 Street, City',
            'nationnalites' => 'Nationality',
            'direction' => 'Direction',
        ]);

        DB::table('users')->insert([
            'name' => 'Ronald Thomas',
            'email' => 'test3@test.com',
            'email_verified_at' => '2024-03-20 12:00:00',
            'password' => Hash::make('12345678'),
            'phone' => '1234567890',
            'image' => 'john.jpg',
            'isActive' => true,
            'code_2FA' => 123456,
            'code_2fa_created_at' => '2024-03-20 12:00:00',
            'isTwoFactorEnabled' => false,
            'role_id' => 4,
            'idErp' => 1,
            'idFiscale' => '300000Am',
            'adress' => '123 Street, City',
            'nationnalites' => 'Nationality',
            'direction' => 'Direction',
            'remember_token' => 'random_token',
            'created_at' => '2024-03-20 12:00:00',
            'updated_at' => '2024-03-20 12:00:00',
        ]);
    }
}
