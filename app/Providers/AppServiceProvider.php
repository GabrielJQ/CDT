<?php

namespace App\Providers;

use App\Contracts\Repositories\TiendaRepositoryInterface;
use App\Repositories\TiendaPostgresqlRepository;
use App\Servicios\ServicioAlcanceUsuario;
use App\Servicios\ServicioMapeoColumnas;
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
        $this->app->bind(
            TiendaRepositoryInterface::class,
            TiendaPostgresqlRepository::class,
        );

        $this->app->singleton(ServicioMapeoColumnas::class, function () {
            return new ServicioMapeoColumnas(config('importacion.column_mapping', []));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ini_set('memory_limit', '2G');

        if ($this->app->environment('production', 'local')) {
            try {
                DB::connection(config('database.imports'))->statement('SET statement_timeout = 120000');
            } catch (\Throwable) {
                // Conexion puede no estar disponible durante migraciones o cache
            }
        }

        View::composer('layouts.app', function ($view) {
            $jerarquia = rescue(
                fn () => $this->app->make(TiendaRepositoryInterface::class)->getJerarquiaRegional(),
                [],
            );

            try {
                $user = request()->user();
                if ($user !== null) {
                    $jerarquia = $this->app->make(ServicioAlcanceUsuario::class)->filtrarJerarquia($user, $jerarquia);
                }
            } catch (\Throwable) {
                $jerarquia = [];
            }

            $view->with('regionesData', $jerarquia);

            try {
                $alcance = $this->app->make(ServicioAlcanceUsuario::class);
                $effectiveFilter = $alcance->filtroEfectivo(request());
                $view->with('currentRegionCookie', $effectiveFilter['region']);
                $view->with('currentUoCookie', $effectiveFilter['uo']);
            } catch (\Throwable) {
                $view->with('currentRegionCookie', '');
                $view->with('currentUoCookie', '');
            }
        });
    }
}
