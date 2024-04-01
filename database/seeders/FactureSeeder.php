<?php

namespace Database\Seeders;

use App\Models\Facture;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FactureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Facture pour le fournisseur Anton Carey
        Facture::create([
            'number' => 'FAC456',
            'invoice_name' => 'Facture Anton Carey',
            'organization' => 'Anton Carey Company',
            'department' => 'Departement Anton Carey',
            'billing_date' => now(),
            'consumption_period' => 'Février 2024',
            'currency' => 'TND',
            'amount' => 1500.75,
            'invoice_file_path' => 'path/vers/le/fichier/facture_anton_carey.pdf',
            'reception_date' => now(),
            'payment_period' => '60 jours',
            'isArchived' => false,
            'etat_id' => 1,
            'borderau_id' => 1,
            'bon_de_commande_id' => null,
            'created_by' => 'Fournisseur',
            'fournisseur_id' => 3, // Remplacez par l'ID du fournisseur Anton Carey
            'agent_bof_id' => null,
        ]);

        for ($i = 0; $i <= 10; $i++) {
            Facture::create([
                'number' => 'FAC30' . $i,
                'invoice_name' => 'Facture Anton Carey',
                'organization' => 'Anton Carey Company',
                'department' => 'Departement Anton Carey',
                'billing_date' => now(),
                'consumption_period' => 'Février 2024',
                'currency' => 'TND',
                'amount' => 1500.75,
                'invoice_file_path' => 'path/vers/le/fichier/facture_anton_carey.pdf',
                'reception_date' => now(),
                'payment_period' => '60 jours',
                'isArchived' => false,
                'etat_id' => 1,
                'borderau_id' => 1,
                'bon_de_commande_id' => null,
                'created_by' => 'Fournisseur',
                'fournisseur_id' => 3, // Remplacez par l'ID du fournisseur Anton Carey
                'agent_bof_id' => null,
            ]);
        }

        Facture::create([
            'number' => 'FAC457',
            'invoice_name' => 'Facture Anton Carey',
            'organization' => 'Anton Carey Company',
            'department' => 'Departement Anton Carey',
            'billing_date' => now(),
            'consumption_period' => 'Février 2024',
            'currency' => 'TND',
            'amount' => 1500.75,
            'invoice_file_path' => 'path/vers/le/fichier/facture_anton_carey.pdf',
            'reception_date' => now(),
            'payment_period' => '60 jours',
            'isArchived' => false,
            'etat_id' => 1,
            'borderau_id' => 1,
            'bon_de_commande_id' => null,
            'created_by' => 'Fournisseur',
            'fournisseur_id' => 3, // Remplacez par l'ID du fournisseur Anton Carey
            'agent_bof_id' => null,
        ]);

        // Facture pour le fournisseur Benji Thomas
        Facture::create([
            'number' => 'FAC789',
            'invoice_name' => 'Facture Benji Thomas',
            'organization' => 'Benji Thomas Company',
            'department' => 'Departement Benji Thomas',
            'billing_date' => now(),
            'consumption_period' => 'Mars 2024',
            'currency' => 'TND',
            'amount' => 2000.80,
            'invoice_file_path' => 'path/vers/le/fichier/facture_benji_thomas.pdf',
            'reception_date' => now(),
            'payment_period' => '60 jours',
            'isArchived' => false,
            'etat_id' => 1,
            'borderau_id' => 1,
            'bon_de_commande_id' => null,
            'created_by' => 'Fournisseur',
            'fournisseur_id' => 4, // Remplacez par l'ID du fournisseur Benji Thomas
            'agent_bof_id' => null,
        ]);

        Facture::create([
            'number' => 'FAC790',
            'invoice_name' => 'Facture Benji Thomas',
            'organization' => 'Benji Thomas Company',
            'department' => 'Departement Benji Thomas',
            'billing_date' => now(),
            'consumption_period' => 'Mars 2024',
            'currency' => 'TND',
            'amount' => 2000.80,
            'invoice_file_path' => 'path/vers/le/fichier/facture_benji_thomas.pdf',
            'reception_date' => now(),
            'payment_period' => '60 jours',
            'isArchived' => false,
            'etat_id' => 1,
            'borderau_id' => 1,
            'bon_de_commande_id' => null,
            'created_by' => 'Fournisseur',
            'fournisseur_id' => 4, // Remplacez par l'ID du fournisseur Benji Thomas
            'agent_bof_id' => null,
        ]);

        // Facture pour le fournisseur John Doe
        Facture::create([
            'number' => 'FAC123',
            'invoice_name' => 'Facture John Doe',
            'organization' => 'Organisation John Doe',
            'department' => 'Département John Doe',
            'billing_date' => now(),
            'consumption_period' => 'Janvier 2024',
            'currency' => 'TND',
            'amount' => 1000.50,
            'invoice_file_path' => 'path/vers/le/fichier/facture_john_doe.pdf',
            'reception_date' => now(),
            'payment_period' => '60 jours',
            'isArchived' => false,
            'etat_id' => 1,
            'borderau_id' => 1,
            'bon_de_commande_id' => null,
            'created_by' => 'Fournisseur',
            'fournisseur_id' => 2, // Remplacez par l'ID du fournisseur John Doe
            'agent_bof_id' => null,
        ]);

        Facture::create([
            'number' => 'FAC124',
            'invoice_name' => 'Facture John Doe',
            'organization' => 'Organisation John Doe',
            'department' => 'Département John Doe',
            'billing_date' => now(),
            'consumption_period' => 'Janvier 2024',
            'currency' => 'TND',
            'amount' => 1000.50,
            'invoice_file_path' => 'path/vers/le/fichier/facture_john_doe.pdf',
            'reception_date' => now(),
            'payment_period' => '60 jours',
            'isArchived' => false,
            'etat_id' => 1,
            'borderau_id' => 1,
            'bon_de_commande_id' => null,
            'created_by' => 'Fournisseur',
            'fournisseur_id' => 2, // Remplacez par l'ID du fournisseur John Doe
            'agent_bof_id' => null,
        ]);
    }
}
