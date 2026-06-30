<?php

namespace App\Livewire;

use App\Servicios\ServicioAlcanceUsuario;
use App\Servicios\ServicioPostgresql;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class DashboardContent extends Component
{
    protected ServicioAlcanceUsuario $alcanceUsuario;

    protected ServicioPostgresql $postgres;

    public int $totalCount = 0;

    public array $criticalSummary = [];

    public int $sinConectividad = 0;

    public int $aperturasEsteMes = 0;

    public array $connectivityKpis = [];

    public array $geoStats = [];

    public array $aperturasPorMes = [];

    public array $directorioStats = [];

    public array $auditoriaKpis = [];

    public string $updatedAt = '';

    public ?string $error = null;

    public string $chartDataJson = '{}';

    private function regionFilters(): array
    {
        return $this->alcanceUsuario->filtroEfectivo(request());
    }

    private function cacheKey(): string
    {
        $filter = $this->regionFilters();

        return sprintf(
            'dashboard_metrics:v%s:region:%s:uo:%s:p%s',
            Cache::get('dashboard_metrics_version', 1),
            $filter['region'] ?: 'all',
            $filter['uo'] ?: 'all',
            $filter['periodo_importacion_id'] ?? '0',
        );
    }

    public function boot(ServicioAlcanceUsuario $alcanceUsuario, ServicioPostgresql $postgres): void
    {
        $this->alcanceUsuario = $alcanceUsuario;
        $this->postgres = $postgres;
    }

    public function mount(): void
    {
        $this->loadData();
        $this->dispatch('dashboard-rendered');
    }

    public function refresh(): void
    {
        Cache::increment('dashboard_metrics_version');
        $this->loadData();
        session()->flash('success', 'Cache actualizado correctamente desde la base local.');
        $this->dispatch('dashboard-rendered');
    }

    private function loadData(): void
    {
        $metrics = Cache::remember(
            $this->cacheKey(),
            now()->addMinutes(10),
            fn () => $this->postgres->obtenerDashboardMetricas($this->regionFilters()),
        );

        $this->totalCount = (int) ($metrics['totalCount'] ?? 0);
        $this->criticalSummary = (array) ($metrics['criticalSummary'] ?? []);
        $this->sinConectividad = (int) ($metrics['sinConectividad'] ?? 0);
        $this->aperturasEsteMes = (int) ($metrics['aperturasEsteMes'] ?? 0);
        $this->connectivityKpis = (array) ($metrics['connectivityKpis'] ?? []);
        $this->geoStats = (array) ($metrics['geoStats'] ?? []);
        $this->aperturasPorMes = (array) ($metrics['aperturasPorMes'] ?? []);
        $this->directorioStats = (array) ($metrics['directorioStats'] ?? []);
        $this->auditoriaKpis = (array) ($metrics['auditoriaKpis'] ?? []);
        $this->updatedAt = now()->toDateTimeString();
        $this->chartDataJson = json_encode([
            'totalCount' => $this->totalCount,
            'connectivityKpis' => $this->connectivityKpis,
            'criticalSummary' => $this->criticalSummary,
            'geoStats' => $this->geoStats,
            'aperturasPorMes' => $this->aperturasPorMes,
            'directorioStats' => $this->directorioStats,
            'auditoriaKpis' => $this->auditoriaKpis,
        ]);
    }

    public function render()
    {
        return view('livewire.dashboard-content');
    }
}
