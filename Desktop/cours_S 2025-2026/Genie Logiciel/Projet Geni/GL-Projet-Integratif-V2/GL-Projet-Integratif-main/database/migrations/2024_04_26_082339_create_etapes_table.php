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
        Schema::create('etapes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facture_id');
            $table->foreign('facture_id')->references('id')->on('factures')->onDelete('cascade');
            $table->unsignedBigInteger('etat_id')->nullable();
            $table->foreign('etat_id')->references('id')->on('etats')->onDelete('set null');
            $table->string('traitParRoleNom')->nullable();
            $table->unsignedBigInteger('traitParId');
            $table->foreign('traitParId')->references('id')->on('users')->onDelete('cascade');
            $table->string('traitParNom')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etapes');
    }
};
