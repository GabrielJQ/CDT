<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioGoogleSheet;
use App\Servicios\ServicioConectividad;
use App\Servicios\ServicioTiendaCritica;
use App\Servicios\ServicioAuditoria;
use App\Servicios\ServicioFecha;

class DashboardController extends Controller
{
    public function __construct(
        private ServicioGoogleSheet $sheet,
        private ServicioConectividad $conectividad,
        private ServicioTiendaCritica $critica,
        private ServicioAuditoria $auditoria,
        private ServicioFecha $fecha,
    ) {}

    public function index()
    {
        $stores = $this->sheet->obtenerTiendas();
        if ($stores === null) {
            abort(503, $this->sheet->getUltimoError() ?? 'No se pudieron obtener los datos del Google Sheet.');
        }

        $stores = $this->applyRegionFilter($stores);
        $totalCount = count($stores);

        return view('dashboard', [
            'totalCount' => $totalCount,
            'connectivityKpis' => $this->conectividad->resumenSimple($stores),
            'criticalSummary' => $this->critica->resumenSimple($stores),
            'sinConectividad' => $this->conectividad->contarSinConectividad($stores),
            'aperturasEsteMes' => $this->contarAperturasEsteMes($stores),
            'geoStats' => $this->calcularGeoStats($stores),
            'aperturasKpi' => $this->calcularAperturasKpi($stores),
            'directorioStats' => $this->calcularDirectorioStats($stores),
            'auditoriaKpis' => $this->auditoria->resumenSimple($stores),
            'updatedAt' => cache()->get('dashboard_updated_at'),
        ]);
    }

    public function refresh()
    {
        $stores = $this->sheet->refrescar();

        if ($stores === null) {
            return back()->with('error', $this->sheet->getUltimoError() ?? 'No se pudieron refrescar los datos desde el Google Sheet.');
        }

        return back()->with('success', 'Datos actualizados correctamente desde el Google Sheet.');
    }

    private function contarAperturasEsteMes(array $stores): int
    {
        $now = now();
        $count = 0;
        foreach ($stores as $store) {
            $fecha = $this->fecha->parsear($store['Fecha_Apertura'] ?? '');
            if ($fecha && $fecha->year === $now->year && $fecha->month === $now->month) $count++;
        }
        return $count;
    }

    private function calcularGeoStats(array $stores): array
    {
        $conCoordenadas = 0;
        $sinCoordenadas = 0;
        foreach ($stores as $store) {
            $lat = trim($store['Latitud'] ?? '');
            $lon = trim($store['Longitud'] ?? '');
            if ($lat !== '' && $lat !== '0' && $lon !== '' && $lon !== '0') {
                $conCoordenadas++;
            } else {
                $sinCoordenadas++;
            }
        }
        return compact('conCoordenadas', 'sinCoordenadas');
    }

    private function calcularAperturasKpi(array $stores): array
    {
        $now = now();
        $total = 0;
        $esteAnio = 0;
        foreach ($stores as $store) {
            $fecha = $this->fecha->parsear($store['Fecha_Apertura'] ?? '');
            if ($fecha) {
                $total++;
                if ($fecha->year === $now->year) $esteAnio++;
            }
        }
        return compact('total', 'esteAnio');
    }

    private function calcularDirectorioStats(array $stores): array
    {
        $trackedColumns = ['TELEFONIA', 'CORREO', 'Cap_Tot', 'Direccion'];
        $incompletos = 0;
        $completos = 0;

        foreach ($stores as $store) {
            $hasEmpty = false;
            foreach ($trackedColumns as $col) {
                $val = trim($store[$col] ?? '');
                if ($val === '' || $val === '0') {
                    $hasEmpty = true;
                    break;
                }
            }
            if ($hasEmpty) $incompletos++;
            else $completos++;
        }
        return compact('incompletos', 'completos');
    }
}
