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

        DB::table('roles')->insert([
            [
                'name' => 'Admin',
            ],
            [
                'name' => 'Agent Bof',
            ],
            [
                'name' => 'Fournisseur',
            ],
            [
                'name' => 'Agent Ap',
            ],
            [
                'name' => 'Agent Fiscaliste',
            ],
            [
                'name' => 'Agent Trésorerie ',
            ],
        ]);
    }
}
