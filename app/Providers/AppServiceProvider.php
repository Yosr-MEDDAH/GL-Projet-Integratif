<?php

namespace App\Providers;

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
        //
    }
}
