<?php

namespace Database\Seeders;

use App\Models\Reclamation;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReclamationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $johnDoe = User::where('idFiscale', 'smjdhf500')->first();
        $antonCarey = User::where('idFiscale', 'jsdfsmlkdfdgddf12311')->first();
        $benjiThomas = User::where('idFiscale', '2sdqfklghf455')->first();


        Reclamation::create([
            'title' => 'Réclamation fournisseur John Doe',
            'text' => 'Description de la réclamation pour John Doe',
            'idFiscale' => $johnDoe->idFiscale,
            'numFacture' => 'FAC123',
            'numCommande' => '123',
            'attached_file' => 'chemin/vers/le/fichier_joint.pdf',
            'fournisseur_id' => $johnDoe->id,
        ]);

        Reclamation::create([
            'title' => 'Réclamation fournisseur Anton Carey',
            'text' => 'Description de la réclamation pour Anton Carey',
            'idFiscale' => $antonCarey->idFiscale,
            'numFacture' => 'FAC789',
            'numCommande' => '456',
            'attached_file' => 'chemin/vers/le/fichier_joint.pdf',
            'fournisseur_id' => $antonCarey->id,
        ]);

        Reclamation::create([
            'title' => 'Réclamation fournisseur Benji Thomas',
            'text' => 'Description de la réclamation pour Benji Thomas',
            'idFiscale' => $benjiThomas->idFiscale,
            'numFacture' => 'FAC654',
            'numCommande' => '789',
            'attached_file' => 'chemin/vers/le/fichier_joint.pdf',
            'fournisseur_id' => $benjiThomas->id,
        ]);
    }
}
