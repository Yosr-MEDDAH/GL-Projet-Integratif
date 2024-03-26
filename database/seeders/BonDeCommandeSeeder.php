<?php

namespace Database\Seeders;

use App\Models\BonDeCommande;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BonDeCommandeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        BonDeCommande::factory()->count(2)->create();
    }
}
