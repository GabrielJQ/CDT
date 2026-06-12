<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioPostgresql;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        private ServicioPostgresql $postgres,
    ) {}

    public function index()
    {
        $metrics = Cache::remember(
            $this->dashboardCacheKey(),
            now()->addMinutes(10),
            fn () => $this->postgres->obtenerDashboardMetricas($this->applyRegionFilter()),
        );

        return view('dashboard', $metrics + [
            'updatedAt' => now()->toDateTimeString(),
            'error' => $this->postgres->getUltimoError(),
        ]);
    }

    public function refresh()
    {
        $this->invalidateDashboardCache();

        return back()->with('success', 'Cache actualizado correctamente desde la base local.');
    }

    public static function invalidateDashboardCache(): void
    {
        Cache::increment('dashboard_metrics_version');
    }

    private function dashboardCacheKey(): string
    {
        $filter = $this->applyRegionFilter();

        return sprintf(
            'dashboard_metrics:v%s:region:%s:uo:%s',
            Cache::get('dashboard_metrics_version', 1),
            $filter['region'] ?: 'all',
            $filter['uo'] ?: 'all',
        );
    }
}
