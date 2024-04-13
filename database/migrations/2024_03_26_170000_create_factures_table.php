<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable(); // Numéro de facture
            $table->string('invoice_name')->nullable(); // Nom de la facture
            $table->string('organization')->nullable(); // Nom de l'organisation
            $table->string('department')->nullable(); // Nom du département
            $table->timestamp('billing_date')->nullable(); // Date de facturation
            $table->string('consumption_period')->nullable(); // Période de consommation
            $table->string('currency')->nullable()->default('TND'); // Devise
            $table->decimal('amount', 10, 3)->nullable(); // Montant
            $table->string('type')->default('3WM')->nullable(); // type
            $table->string('invoice_file_path')->nullable(); // Chemin du fichier de la facture
            $table->timestamp('reception_date')->nullable(); // Date de réception de la facture
            $table->string('payment_period')->nullable()->default('60 jours');
            $table->boolean('isArchived')->nullable();
            $table->json('pieces_jointes')->nullable();
            $table->unsignedBigInteger('objet_facture_id')->nullable();
            $table->foreign('objet_facture_id')->references('id')->on('objet_factures');
            $table->unsignedBigInteger('etat_id')->nullable(); // Clé étrangère pour l'état de la facture
            $table->foreign('etat_id')->references('id')->on('etats')->onDelete('set null'); // Référence à la table des états
            $table->unsignedBigInteger('borderau_id')->nullable(); // Clé étrangère pour le bordereau
            $table->foreign('borderau_id')->references('id')->on('bordereaux')->onDelete('set null');
            $table->unsignedBigInteger('bon_de_commande_id')->nullable();
            $table->foreign('bon_de_commande_id')->references('id')->on('bon_de_commandes')->onDelete('cascade');
            $table->string('created_by')->nullable();
            $table->unsignedBigInteger('fournisseur_id')->nullable(); // ID du fournisseur
            $table->foreign('fournisseur_id')->references('id')->on('users'); // cascade
            $table->unsignedBigInteger('agent_bof_id')->nullable(); // ID du fournisseur
            $table->foreign('agent_bof_id')->references('id')->on('users'); // cascade
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
