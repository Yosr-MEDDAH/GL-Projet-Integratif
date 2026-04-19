<?php

namespace App\Providers;

use App\Models\Facture;
use App\Observers\FactureObserver;
use Illuminate\Support\ServiceProvider;
use App\Interfaces\UtilisateurFactoryInterface;
use App\Services\UtilisateurFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            UtilisateurFactoryInterface::class,
            UtilisateurFactory::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Observer centralise la detection des changements de statut des factures.
        Facture::observe(FactureObserver::class);
    }
}
