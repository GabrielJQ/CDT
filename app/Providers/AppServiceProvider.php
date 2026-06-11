<?php

namespace App\Providers;

use App\Servicios\ServicioPostgresql;
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
