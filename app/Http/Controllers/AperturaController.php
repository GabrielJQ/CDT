<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioFecha;
use App\Servicios\ServicioGoogleSheet;
use Illuminate\Http\Request;

class AperturaController extends Controller
{
    public function __construct(
        private ServicioGoogleSheet $sheet,
        private ServicioFecha $fecha,
    ) {}

    public function index(Request $request)
    {
        $stores = $this->sheet->obtenerTiendas($this->applyRegionFilter());
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
            $store['_antiguedad'] = $fecha ? (int) round($fecha->diffInMonths(now())).' meses' : '—';

            return $store;
        });

        $filtered = $evaluated->filter(function ($store) use ($filters) {
            if ($filters['almacen'] !== '') {
                $nombre = $store['Nombre_Almacen'] ?? '';
                if (! str_contains(mb_strtoupper($nombre), mb_strtoupper($filters['almacen']))) {
                    return false;
                }
            }
            if ($filters['desde'] !== '') {
                $desde = $this->fecha->parsear($filters['desde']);
                $fecha = $store['_fecha_apertura'];
                if ($desde && ($fecha === null || $fecha->lt($desde))) {
                    return false;
                }
            }
            if ($filters['hasta'] !== '') {
                $hasta = $this->fecha->parsear($filters['hasta']);
                $fecha = $store['_fecha_apertura'];
                if ($hasta && ($fecha === null || $fecha->gt($hasta))) {
                    return false;
                }
            }

            return true;
        })->sortByDesc(function ($store) {
            return $store['_fecha_apertura']?->timestamp ?? 0;
        })->values()->all();

        if ($request->query('export') === 'csv') {
            return ServicioExportacion::csvResponse($filtered, [
                'Nombre_Almacen' => 'Almacén',
                'No_Tienda_Actual' => 'Tienda #',
                'Localidad' => 'Localidad',
                'Municipio' => 'Municipio',
                'Fecha_Apertura' => 'Fecha Apertura',
                '_fecha_apertura' => 'Apertura (parseada)',
                '_antiguedad' => 'Antigüedad',
            ], 'aperturas.csv');
        }

        return view('aperturas', [
            'stores' => $filtered,
            'totalCount' => $totalCount,
            'filteredCount' => count($filtered),
            'kpis' => $this->calcularKpis($evaluated->all(), $filtered),
            'filters' => $filters,
            'updatedAt' => now()->toDateTimeString(),
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
            if ($fecha->year === $now->year && $fecha->month === $now->month) {
                $esteMes++;
            }
            if ($fecha->year === $now->year) {
                $esteAnio++;
            }
        }

        return [
            'total' => $totalFiltered,
            'esteMes' => $esteMes,
            'esteAnio' => $esteAnio,
            'sinFecha' => $sinFecha,
        ];
    }
}
