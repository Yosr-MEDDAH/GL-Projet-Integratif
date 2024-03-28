<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('bon_de_commandes', function (Blueprint $table) {
            $table->id();
            $table->string('num_commande')->nullable(); // Numéro de commande
            $table->string('created_by')->nullable(); // Créé par
            $table->unsignedBigInteger('idErp')->nullable(); // ID ERP
            $table->string('delai_paiement')->nullable(); // Délai de paiement
            $table->unsignedBigInteger('fournisseur_id');
            $table->foreign('fournisseur_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bon_de_commandes');
    }
};
