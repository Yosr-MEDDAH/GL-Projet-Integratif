<?php


// database/seeders/RolesSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Insert roles into the roles table
        DB::table('roles')->insert([
            [
                'name' => 'Admin',
            ],
            [
                'name' => 'User',
            ],
            // Add more roles as needed
        ]);
    }
}
