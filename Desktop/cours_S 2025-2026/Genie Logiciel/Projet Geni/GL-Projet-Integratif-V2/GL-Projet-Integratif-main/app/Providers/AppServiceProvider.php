<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
// AVANT (DIP)
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}


// APRÈS (DIP)
use App\Interfaces\ValidationFactureInterface;
use App\Services\ValidationFactureService;
use App\Interfaces\UtilisateurFactoryInterface;
use App\Factories\UserFactoryProvider;

/**
 * ============================================================
 * AppServiceProvider — Conteneur IoC (Inversion of Control)
 * ============================================================
 *
 * SOLID — Dependency Inversion Principle (DIP) :
 *   C'est ici que sont enregistrés les bindings
 *   interface -> implémentation concrète.
 *
 *   Le conteneur IoC de Laravel résout automatiquement les
 *   interfaces injectées dans les constructeurs des Controllers
 *   et Services.
 *
 * Les Controllers dépendent des INTERFACES :
 *   ValidationFactureController -> ValidationFactureInterface
 *   AdministrateurController    -> UtilisateurFactoryInterface
 *
 * Les implémentations concrètes sont enregistrées ici
 * et injectées automatiquement par Laravel.
 * ============================================================
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Binding DIP :
     *   Quand Laravel voit ValidationFactureInterface dans un
     *   constructeur, il instancie ValidationFactureService.
     */
    public function register(): void
    {
        // SOLID DIP — Validation de factures
        $this->app->bind(
            ValidationFactureInterface::class,
            ValidationFactureService::class
        );

        // SOLID DIP — Factory d'utilisateurs (déjà existant)
        $this->app->bind(
            UtilisateurFactoryInterface::class,
            UserFactoryProvider::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
