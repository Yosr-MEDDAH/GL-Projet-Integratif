<?php

namespace Database\Seeders;

use App\Models\BonDeCommande;
use App\Models\Bordereau;
use App\Models\Etat;
use App\Models\Facture;
use App\Models\ObjetFacture;
use App\Models\PieceJointeFacture;
use App\Models\Reclamation;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DataBaseSeederGeneral extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un bon de commande pour le fournisseur John Doe
        $this->call(RolesSeeder::class);

        User::factory()->create([
            'name' => 'John steve',
            'email' => 'john100@example.com',
            'password' => Hash::make('12345678'),
            'phone' => '1234567890',
            'image' => 'john.jpg',
            'isActive' => true,
            'code_2FA' => 123456,
            'isTwoFactorEnabled' => false,
            'role_id' => 1,
            'idErp' => 1,
            'idFiscale' => null,
            'adress' => '123 Street, City',
            'nationnalites' => 'Nationality',
            'direction' => 'Direction',
        ]);


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
            'idFiscale' => null,
            'adress' => '123 Street, City',
            'nationnalites' => 'Nationality',
            'direction' => 'Direction',
            'remember_token' => 'random_token',
            'created_at' => '2024-03-20 12:00:00',
            'updated_at' => '2024-03-20 12:00:00',
        ]);

        DB::table('users')->insert([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'email_verified_at' => '2024-03-20 12:00:00',
            'password' => Hash::make('12345678'),
            'phone' => '1234567890',
            'image' => 'alice.jpg',
            'isActive' => true,
            'code_2FA' => 123456,
            'code_2fa_created_at' => '2024-03-20 12:00:00',
            'isTwoFactorEnabled' => false,
            'role_id' => 4, // ID du rôle Agent Ap
            'idErp' => 1,
            'idFiscale' => null,
            'adress' => '123 Street, City',
            'nationnalites' => 'Nationality',
            'direction' => 'Direction',
            'remember_token' => 'random_token',
            'created_at' => '2024-03-20 12:00:00',
            'updated_at' => '2024-03-20 12:00:00',
        ]);

        DB::table('users')->insert([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'email_verified_at' => '2024-03-21 12:00:00',
            'password' => Hash::make('12345678'),
            'phone' => '1234567890',
            'image' => 'bob.jpg',
            'isActive' => true,
            'code_2FA' => 123456,
            'code_2fa_created_at' => '2024-03-21 12:00:00',
            'isTwoFactorEnabled' => false,
            'role_id' => 5, // ID du rôle Agent Fiscaliste
            'idErp' => 1,
            'idFiscale' => null,
            'adress' => '456 Avenue, Town',
            'nationnalites' => 'Nationality',
            'direction' => 'Direction',
            'remember_token' => 'random_token',
            'created_at' => '2024-03-21 12:00:00',
            'updated_at' => '2024-03-21 12:00:00',
        ]);

        DB::table('users')->insert([
            'name' => 'Charlie Brown',
            'email' => 'charlie@example.com',
            'email_verified_at' => '2024-03-22 12:00:00',
            'password' => Hash::make('12345678'),
            'phone' => '1234567890',
            'image' => 'charlie.jpg',
            'isActive' => true,
            'code_2FA' => 123456,
            'code_2fa_created_at' => '2024-03-22 12:00:00',
            'isTwoFactorEnabled' => false,
            'role_id' => 6, // ID du rôle Agent Trésorerie
            'idErp' => 1,
            'idFiscale' => null,
            'adress' => '789 Road, Village',
            'nationnalites' => 'Nationality',
            'direction' => 'Direction',
            'remember_token' => 'random_token',
            'created_at' => '2024-03-22 12:00:00',
            'updated_at' => '2024-03-22 12:00:00',
        ]);

        DB::table('users')->insert([
            'name' => 'Emma Wilson',
            'email' => 'emma@example.com',
            'email_verified_at' => '2024-03-23 12:00:00',
            'password' => Hash::make('12345678'),
            'phone' => '1234567890',
            'image' => 'emma.jpg',
            'isActive' => true,
            'code_2FA' => 123456,
            'code_2fa_created_at' => '2024-03-23 12:00:00',
            'isTwoFactorEnabled' => false,
            'role_id' => 4, // ID du rôle Agent Ap
            'idErp' => 1,
            'idFiscale' => null,
            'adress' => '101 Main Street, City',
            'nationnalites' => 'Nationality',
            'direction' => 'Direction',
            'remember_token' => 'random_token',
            'created_at' => '2024-03-23 12:00:00',
            'updated_at' => '2024-03-23 12:00:00',
        ]);
        
        DB::table('users')->insert([
            'name' => 'Oliver Smith',
            'email' => 'oliver@example.com',
            'email_verified_at' => '2024-03-24 12:00:00',
            'password' => Hash::make('12345678'),
            'phone' => '1234567890',
            'image' => 'oliver.jpg',
            'isActive' => true,
            'code_2FA' => 123456,
            'code_2fa_created_at' => '2024-03-24 12:00:00',
            'isTwoFactorEnabled' => false,
            'role_id' => 5, // ID du rôle Agent Fiscaliste
            'idErp' => 1,
            'idFiscale' => null,
            'adress' => '202 Elm Street, Town',
            'nationnalites' => 'Nationality',
            'direction' => 'Direction',
            'remember_token' => 'random_token',
            'created_at' => '2024-03-24 12:00:00',
            'updated_at' => '2024-03-24 12:00:00',
        ]);
        
        DB::table('users')->insert([
            'name' => 'William Johnson',
            'email' => 'william@example.com',
            'email_verified_at' => '2024-03-25 12:00:00',
            'password' => Hash::make('12345678'),
            'phone' => '1234567890',
            'image' => 'william.jpg',
            'isActive' => true,
            'code_2FA' => 123456,
            'code_2fa_created_at' => '2024-03-25 12:00:00',
            'isTwoFactorEnabled' => false,
            'role_id' => 6, // ID du rôle Agent Trésorerie
            'idErp' => 1,
            'idFiscale' => null,
            'adress' => '303 Oak Street, Village',
            'nationnalites' => 'Nationality',
            'direction' => 'Direction',
            'remember_token' => 'random_token',
            'created_at' => '2024-03-25 12:00:00',
            'updated_at' => '2024-03-25 12:00:00',
        ]);
        




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


        Etat::create(['name_etat' => 'Attente']);
        Etat::create(['name_etat' => 'Envoyé']);
        Etat::create(['name_etat' => 'Accepté']);
        Etat::create(['name_etat' => 'Refusé']);
        Etat::create(['name_etat' => 'Cloturé']);



        Bordereau::create([
            'date_sent' => now(),
            'folder' => 'Folder1',
            'status' => 'En cours',
            'nature' => '3WM',
            'reference' => 'REF123',
        ]);



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


        $donneesPiecesJointes = [
            "PV DE RÉCEPTION",
            "BON DE COMMANDE",
            "BON DE LIVRAISON",
            "COPIE DE CONTRAT",
            "APPEL À LA FACTURATION",
            "RELEVÉ CONSOMMATION",
            "CIN",
        ];


        foreach ($donneesPiecesJointes as $nomPieceJointe) {
            $pieceJointe = new PieceJointeFacture();
            $pieceJointe->namePJ = $nomPieceJointe;
            $pieceJointe->createdBy = null;
            $pieceJointe->created_at = now();
            $pieceJointe->save();
        }



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
            'objet_facture_id' => 1,
            'pieces_jointes' => [1, 2, 3],
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
                'objet_facture_id' => 1,
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
            'objet_facture_id' => 1,
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
            'objet_facture_id' => 1,
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
            'objet_facture_id' => 1,
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
            'objet_facture_id' => 1,
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
            'objet_facture_id' => 1,
            'etat_id' => 1,
            'borderau_id' => 1,
            'bon_de_commande_id' => null,
            'created_by' => 'Fournisseur',
            'fournisseur_id' => 2, // Remplacez par l'ID du fournisseur John Doe
            'agent_bof_id' => null,
        ]);




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


        DB::table('mailers')->insert([
            'transport' => 'smtp',
            'host' => 'sandbox.smtp.mailtrap.io',
            'port' => 2525,
            'encryption' => 'tls',
            'username' => '378d47aed4598f',
            'password' => 'f6ad1c1829385f',
            'timeout' => null,
            'local_domain' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);



        DB::table('fournisseurs_sans_comptes')->insert([
            'name' => 'Fournisseur A',
            'email' => 'fournisseurA@example.com',
            'phone' => '0123456789',
            'idErp' => null,
            'idFiscale' => 'ABCDE12345',
            'adress' => '123 Rue de la République',
            'nationnalites' => 'Française',
            'created_at' => now(),
            'updated_at' => now(),
        ]);


        DB::table('fournisseurs_sans_comptes')->insert([
            'name' => 'Fournisseur B',
            'email' => 'fournisseurB@example.com',
            'phone' => '0987654321',
            'idErp' => 1,
            'idFiscale' => 'FGHIJ67890',
            'adress' => '456 Avenue des Champs-Élysées',
            'nationnalites' => 'Belge',
            'created_at' => now(),
            'updated_at' => now(),
        ]);



        DB::table('fournisseurs_sans_comptes')->insert([
            'name' => 'Fournisseur C',
            'email' => 'fournisseurC@example.com',
            'phone' => '1122334455',
            'idErp' => 1,
            'idFiscale' => 'KLMNO54321',
            'adress' => null,
            'nationnalites' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);



        DB::table('fournisseurs_sans_comptes')->insert([
            'name' => 'Fournisseur D',
            'email' => 'fournisseurD@example.com',
            'phone' => '5544332211',
            'idErp' => null,
            'idFiscale' => "abcdefghijilk",
            'adress' => null,
            'nationnalites' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
