<?php

namespace App\Servicios;

use App\Servicios\Modulos\ServicioConsultasTiendas;

class ServicioDashboardMetricas
{
    public function __construct(
        private ServicioConsultasTiendas $consultas,
        private ServicioKpiTiendas $kpiTiendas,
    ) {}

    public function obtenerDashboardMetricas(array $regionFilters, bool $usarDerivados, array $trackedDirectorioColumns): array
    {
        $base = $this->consultas->conexion()->table('tiendas');
        $this->consultas->aplicarPeriodoActivo($base, $regionFilters);
        $this->consultas->aplicarFiltroRegional($base, $regionFilters);

        $total = (clone $base)->count();
        $aperturasPorMes = $this->kpiTiendas->aperturasPorMes(clone $base);
        $directorioStats = $this->kpiTiendas->statsDirectorio(clone $base, $trackedDirectorioColumns);
        $completos = max(0, $total - ($directorioStats['incompletos'] ?? 0));

        return [
            'totalCount' => $total,
            'connectivityKpis' => $this->kpiTiendas->kpisConectividad(clone $base),
            'criticalSummary' => $this->kpiTiendas->resumenCriticidad(clone $base, $usarDerivados),
            'sinConectividad' => $this->kpiTiendas->sinConectividadCount(clone $base),
            'aperturasEsteMes' => $this->kpiTiendas->aperturasEsteMesCount(clone $base),
            'geoStats' => $this->kpiTiendas->geoStats(clone $base, $usarDerivados),
            'aperturasKpi' => $this->kpiTiendas->aperturasKpiDashboard(clone $base),
            'aperturasPorMes' => $aperturasPorMes,
            'directorioStats' => ['completos' => $completos, 'incompletos' => (int) ($directorioStats['incompletos'] ?? 0)],
            'auditoriaKpis' => $this->kpiTiendas->kpisAuditoria(clone $base, $usarDerivados),
        ];
    }
}
