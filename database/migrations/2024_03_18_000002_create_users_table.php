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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->default("12345678");
            $table->string('phone')->nullable();
            $table->string('image')->nullable();
            $table->boolean('notification_toggle')->default(1);
            $table->boolean('isActive')->default(1);
            $table->unsignedBigInteger('code_2FA')->nullable();
            $table->timestamp('code_2fa_created_at')->nullable();
            $table->string('refresh_token')->nullable();
            $table->timestamp('refreshToken_created_at')->nullable();
            $table->boolean('isTwoFactorEnabled')->nullable();
            $table->integer('idErp')->nullable();
            $table->string('idFiscale')->nullable();
            $table->string('adress')->nullable();
            $table->string('nationnalites')->nullable();
            $table->string('direction')->nullable();
            $table->json('type_facture_ids')->nullable();
            $table->unsignedBigInteger('role_id');
            $table->foreign('role_id')->references('id')->on('roles');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /*  'direction'  'idErp',
        'idFiscale',
        'adresse',
        'nationnalite',*/


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
