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
            $table->string('currency')->nullable(); // Devise
            $table->decimal('amount', 10, 2)->nullable(); // Montant
            $table->string('invoice_file_path')->nullable(); // Chemin du fichier de la facture
            $table->timestamp('reception_date')->nullable(); // Date de réception de la facture
            $table->unsignedBigInteger('etat_id')->nullable(); // Clé étrangère pour l'état de la facture
            $table->foreign('etat_id')->references('id')->on('etats')->onDelete('set null'); // Référence à la table des états
            $table->unsignedBigInteger('fournisseur_id')->nullable(); // ID du fournisseur
            $table->foreign('fournisseur_id')->references('id')->on('users');
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
