<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioAuditoria;
use App\Servicios\ServicioConectividad;
use App\Servicios\ServicioFecha;
use App\Servicios\ServicioGoogleSheet;
use App\Servicios\ServicioTiendaCritica;

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
        $stores = $this->sheet->obtenerTiendas($this->applyRegionFilter());
        $totalCount = count($stores);

        return view('dashboard', [
            'totalCount' => $totalCount,
            'connectivityKpis' => $this->conectividad->resumenSimple($stores),
            'criticalSummary' => $this->critica->resumenSimple($stores),
            'sinConectividad' => $this->conectividad->contarSinConectividad($stores),
            'aperturasEsteMes' => $this->contarAperturasEsteMes($stores),
            'geoStats' => $this->calcularGeoStats($stores),
            'aperturasKpi' => $this->calcularAperturasKpi($stores),
            'aperturasPorMes' => $this->calcularAperturasPorMes($stores),
            'directorioStats' => $this->calcularDirectorioStats($stores),
            'auditoriaKpis' => $this->auditoria->resumenSimple($stores),
            'updatedAt' => now()->toDateTimeString(),
            'error' => $this->sheet->getUltimoError(),
        ]);
    }

    public function refresh()
    {
        $this->sheet->refrescar();

        if ($this->sheet->getUltimoError()) {
            return back()->with('error', $this->sheet->getUltimoError());
        }

        return back()->with('success', 'Datos actualizados correctamente desde el Google Sheet.');
    }

    private function contarAperturasEsteMes(array $stores): int
    {
        $now = now();
        $count = 0;
        foreach ($stores as $store) {
            $fecha = $this->fecha->parsear($store['Fecha_Apertura'] ?? '');
            if ($fecha && $fecha->year === $now->year && $fecha->month === $now->month) {
                $count++;
            }
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
                if ($fecha->year === $now->year) {
                    $esteAnio++;
                }
            }
        }

        return compact('total', 'esteAnio');
    }

    private function calcularAperturasPorMes(array $stores): array
    {
        $nombres = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $meses = [];
        $now = now();
        for ($i = 11; $i >= 0; $i--) {
            $date = (clone $now)->subMonths($i);
            $meses[$date->format('Y-m')] = ['label' => $nombres[(int) $date->format('n') - 1], 'count' => 0];
        }
        foreach ($stores as $store) {
            $fecha = $this->fecha->parsear($store['Fecha_Apertura'] ?? '');
            if ($fecha) {
                $key = $fecha->format('Y-m');
                if (isset($meses[$key])) {
                    $meses[$key]['count']++;
                }
            }
        }

        return array_values($meses);
    }

    private function calcularDirectorioStats(array $stores): array
    {
        $incompletos = 0;
        $completos = 0;

        foreach ($stores as $store) {
            $hasEmpty = false;
            foreach (DirectorioController::TRACKED_COLUMNS as $col) {
                $val = trim($store[$col] ?? '');
                if ($val === '' || $val === '0') {
                    $hasEmpty = true;
                    break;
                }
            }
            if ($hasEmpty) {
                $incompletos++;
            } else {
                $completos++;
            }
        }

        return compact('incompletos', 'completos');
    }
}
