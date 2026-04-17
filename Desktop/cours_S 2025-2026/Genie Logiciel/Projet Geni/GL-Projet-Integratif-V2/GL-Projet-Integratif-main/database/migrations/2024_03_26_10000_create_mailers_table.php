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
        Schema::create('mailers', function (Blueprint $table) {
            $table->id();
            $table->string('transport')->default('smtp');
            $table->string('host')->default('sandbox.smtp.mailtrap.io');
            $table->unsignedInteger('port')->default(2525);
            $table->string('encryption')->default('tls');
            $table->string('username')->default('378d47aed4598f');
            $table->string('password')->default('f6ad1c1829385f');
            $table->unsignedInteger('timeout')->nullable();
            $table->string('local_domain')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mailers');
    }
};
