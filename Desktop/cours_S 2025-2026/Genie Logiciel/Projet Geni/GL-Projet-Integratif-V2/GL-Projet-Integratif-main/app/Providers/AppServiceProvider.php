<?php

namespace App\Providers;

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
    // AVANT (DIP)

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
    /**
     * Register any application services.
     *
     * Binding DIP :
     *   Quand Laravel voit ValidationFactureInterface dans un
     *   constructeur, il instancie ValidationFactureService.
     */
    public function registerDIP(): void
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
}
