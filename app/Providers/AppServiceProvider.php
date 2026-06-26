<?php

namespace App\Providers;

use App\Contracts\Repositories\TiendaRepositoryInterface;
use App\Repositories\TiendaPostgresqlRepository;
use App\Servicios\ServicioAlcanceUsuario;
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
                    $jerarquia = app(ServicioAlcanceUsuario::class)->filtrarJerarquia($user, $jerarquia);
                }
            } catch (\Throwable) {
                $jerarquia = [];
            }

            $view->with('regionesData', $jerarquia);
        });
    }
}
