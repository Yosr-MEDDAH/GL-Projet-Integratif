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
        Schema::create('bordereaux', function (Blueprint $table) {
            $table->id();
            $table->timestamp('date_sent')->nullable(); // Date sent
            $table->string('folder')->nullable(); // Folder
            $table->string('status')->nullable(); // Status
            $table->string('nature')->nullable(); // Nature
            $table->string('reference')->nullable(); // Reference
            $table->timestamps(); // Keep automatic creation and update dates
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bordereaus');
    }
};
