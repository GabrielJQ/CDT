<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioGoogleSheet;
use App\Servicios\ServicioFecha;
use Illuminate\Http\Request;

class AperturaController extends Controller
{
    public function __construct(
        private ServicioGoogleSheet $sheet,
        private ServicioFecha $fecha,
    ) {}

    public function index(Request $request)
    {
        $stores = $this->sheet->obtenerTiendas();
        if ($stores === null) {
            return $this->errorView('No se pudieron obtener los datos del Google Sheet.');
        }

        $stores = $this->applyRegionFilter($stores);
        $totalCount = count($stores);

        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'desde' => $request->query('desde', ''),
            'hasta' => $request->query('hasta', ''),
        ];

        $evaluated = collect($stores)->map(function ($store) {
            $fechaRaw = $store['Fecha_Apertura'] ?? '';
            $fecha = $this->fecha->parsear($fechaRaw);
            $store['_fecha_apertura'] = $fecha;
            $store['_antiguedad'] = $fecha ? $fecha->diffInMonths(now()) . ' meses' : '—';
            return $store;
        });

        $filtered = $evaluated->filter(function ($store) use ($filters) {
            if ($filters['almacen'] !== '') {
                $nombre = $store['Nombre_Almacen'] ?? '';
                if (!str_contains(mb_strtoupper($nombre), mb_strtoupper($filters['almacen']))) {
                    return false;
                }
            }
            if ($filters['desde'] !== '') {
                $desde = $this->fecha->parsear($filters['desde']);
                $fecha = $store['_fecha_apertura'];
                if ($desde && ($fecha === null || $fecha->lt($desde))) return false;
            }
            if ($filters['hasta'] !== '') {
                $hasta = $this->fecha->parsear($filters['hasta']);
                $fecha = $store['_fecha_apertura'];
                if ($hasta && ($fecha === null || $fecha->gt($hasta))) return false;
            }
            return true;
        })->sortByDesc(function ($store) {
            return $store['_fecha_apertura']?->timestamp ?? 0;
        })->values()->all();

        return view('aperturas', [
            'stores' => $filtered,
            'totalCount' => $totalCount,
            'filteredCount' => count($filtered),
            'kpis' => $this->calcularKpis($evaluated->all(), $filtered),
            'filters' => $filters,
            'updatedAt' => cache()->get('dashboard_updated_at'),
        ]);
    }

    private function errorView(string $message)
    {
        $filters = ['almacen' => '', 'desde' => '', 'hasta' => ''];
        return view('aperturas', [
            'stores' => [],
            'totalCount' => 0,
            'filteredCount' => 0,
            'kpis' => ['total' => 0, 'esteMes' => 0, 'esteAnio' => 0, 'sinFecha' => 0],
            'filters' => $filters,
            'error' => $message,
            'updatedAt' => null,
        ]);
    }

    private function calcularKpis(array $allStores, array $filtered): array
    {
        $now = now();
        $esteMes = 0;
        $esteAnio = 0;
        $sinFecha = 0;
        $totalFiltered = count($filtered);

        foreach ($filtered as $store) {
            $fecha = $store['_fecha_apertura'] ?? null;
            if ($fecha === null) {
                $sinFecha++;
                continue;
            }
            if ($fecha->year === $now->year && $fecha->month === $now->month) $esteMes++;
            if ($fecha->year === $now->year) $esteAnio++;
        }

        return [
            'total' => $totalFiltered,
            'esteMes' => $esteMes,
            'esteAnio' => $esteAnio,
            'sinFecha' => $sinFecha,
        ];
    }
}
