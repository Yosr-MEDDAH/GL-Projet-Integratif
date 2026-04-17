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
        Schema::create('directions_centrales', function (Blueprint $table) {
            $table->id();
            $table->string('nomDirectionsCentrales', 100)->default('');
            $table->string('directeur')->default('');
            $table->string('profil')->default('');
            $table->string('created_by')->default('Admin');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('directions_centrales');
    }
};
