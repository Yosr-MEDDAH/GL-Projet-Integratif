<?php

namespace App\Providers;

use App\Adapters\ErpAdapterInterface;
use App\Adapters\OracleErpAdapter;
use App\Adapters\OracleErpApiClient;
use Illuminate\Support\ServiceProvider;

/**
 * Lie l'ErpAdapterInterface à l'implémentation OracleErpAdapter
 * dans le conteneur IoC de Laravel.
 *
 * Enregistrer ce provider dans config/app.php → 'providers' :
 *   App\Providers\ErpAdapterServiceProvider::class,
 */
class ErpAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ErpAdapterInterface::class, function ($app) {
            $oracleClient = new OracleErpApiClient(
                baseUrl: config('services.oracle_erp.base_url'),
                apiKey:  config('services.oracle_erp.api_key'),
            );

            return new OracleErpAdapter($oracleClient);
        });
    }
}
