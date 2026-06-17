<?php

namespace App\Providers;

use App\Servicios\ServicioPostgresql;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        ini_set('memory_limit', '2G');

        if ($this->app->environment('production', 'local')) {
            try {
                DB::connection('pgsql_imports')->statement('SET statement_timeout = 120000');
            } catch (\Throwable) {
                // Conexion puede no estar disponible durante migraciones o cache
            }
        }

        View::composer('layouts.app', function ($view) {
            try {
                $postgres = app(ServicioPostgresql::class);
                $view->with('regionesData', $postgres->obtenerJerarquiaRegional());
            } catch (\Throwable) {
                $view->with('regionesData', []);
            }
        });
    }
}
