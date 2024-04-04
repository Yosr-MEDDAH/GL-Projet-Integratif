<?php

namespace Database\Seeders;

use App\Models\ObjetFacture;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ObjetFactureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $donneesObjetsFacture = [
            ['objet_name' => 'NOUVELLE FACTURE'],
            ['objet_name' => 'ANNULE ET REMPLACE'],
            ['objet_name' => 'PÉNALITÉ'],
            ['objet_name' => 'PÉNALITÉ DE RETARD'],
            ['objet_name' => 'NOTE DE REMBOURSEMENT'],
            ['objet_name' => 'AVOIR'],
            ['objet_name' => 'NOTE DE DEBIT'],
            ['objet_name' => 'MEMOIRE DE REGLEMENT - MR'],
            ['objet_name' => 'OUVERTURE DE LETTRE DE CREDIT (LC)'],
            ['objet_name' => 'FICHE D\'INTERVENTION'],
        ];


        foreach ($donneesObjetsFacture as $objetFacture) {
            $objet = new ObjetFacture();
            $objet->objet_name = $objetFacture['objet_name'];
            $objet->createdBy = null;
            $objet->created_at = now();
            $objet->save();
        }




    }
}
