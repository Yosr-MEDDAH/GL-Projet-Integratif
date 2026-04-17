<?php

namespace Database\Seeders;

use App\Models\Etat;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EtatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Etat::create(['name_etat' => 'Attente']);
        Etat::create(['name_etat' => 'Envoyé']);
        Etat::create(['name_etat' => 'Accepté']);
        Etat::create(['name_etat' => 'Refusé']);
        Etat::create(['name_etat' => 'Cloturé']);
    }
}
