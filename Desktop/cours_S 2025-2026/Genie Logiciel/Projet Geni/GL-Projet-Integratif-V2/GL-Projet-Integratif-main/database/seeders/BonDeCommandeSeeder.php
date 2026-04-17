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
        // Créer un bon de commande pour le fournisseur John Doe
        BonDeCommande::create([
            'num_commande' => 123,
            'created_by' => 'Admin',
            'idErp' => 1,
            'delai_paiement' => '30 jours',
            'hasInvoice' => 1,
            'four_idFiscale' => 'smjdhf500',
        ]);

        BonDeCommande::create([
            'num_commande' => 124,
            'created_by' => 'Admin',
            'idErp' => 1,
            'delai_paiement' => '30 jours',
            'hasInvoice' => 1,
            'four_idFiscale' => 'smjdhf500',
        ]);

        // Créer un bon de commande pour le fournisseur Anton Carey
        BonDeCommande::create([
            'num_commande' => 456,
            'created_by' => 'Admin',
            'idErp' => 1,
            'delai_paiement' => '30 jours',
            'hasInvoice' => 1,
            'four_idFiscale' => 'jsdfsmlkdfdgddf12311',
        ]);

        for ($i = 1; $i <= 10; $i++) {
            BonDeCommande::create([
                'num_commande' => $i,
                'delai_paiement' => '30 jours',
                'created_by' => 'Admin',
                'idErp' => 1,
                'hasInvoice' => 1,
                'four_idFiscale' => 'jsdfsmlkdfdgddf12311',
            ]);
        }
        // Créer un bon de commande pour le fournisseur Benji Thomas
        BonDeCommande::create([
            'num_commande' => 789,
            'created_by' => 'Admin',
            'idErp' => 1,
            'delai_paiement' => '30 jours',
            'hasInvoice' => 1,
            'four_idFiscale' => '2sdqfklghf455',
        ]);

        BonDeCommande::create([
            'num_commande' => 790,
            'created_by' => 'Admin',
            'idErp' => 1,
            'delai_paiement' => '30 jours',
            'hasInvoice' => 1,
            'four_idFiscale' => '2sdqfklghf455',
        ]);
    }
}
