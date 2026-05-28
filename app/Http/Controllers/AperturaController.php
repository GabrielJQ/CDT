<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class AperturaController extends Controller
{
    public function index(Request $request)
    {
        $stores = $this->getStores();
        if ($stores === null) {
            return $this->errorView('No se pudieron obtener los datos del Google Sheet.');
        }

        $totalCount = count($stores);

        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'desde' => $request->query('desde', ''),
            'hasta' => $request->query('hasta', ''),
        ];

        $evaluated = collect($stores)->map(function ($store) {
            $fechaRaw = $store['Fecha_Apertura'] ?? '';
            $fecha = $this->parseDate($fechaRaw);
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
                $desde = $this->parseDate($filters['desde']);
                $fecha = $store['_fecha_apertura'];
                if ($desde && ($fecha === null || $fecha->lt($desde))) return false;
            }
            if ($filters['hasta'] !== '') {
                $hasta = $this->parseDate($filters['hasta']);
                $fecha = $store['_fecha_apertura'];
                if ($hasta && ($fecha === null || $fecha->gt($hasta))) return false;
            }
            return true;
        })->sortByDesc(function ($store) {
            return $store['_fecha_apertura']?->timestamp ?? 0;
        })->values()->all();

        $filteredCount = count($filtered);

        $kpis = $this->calculateKpis($evaluated->all(), $filtered);

        return view('aperturas', [
            'stores' => $filtered,
            'totalCount' => $totalCount,
            'filteredCount' => $filteredCount,
            'kpis' => $kpis,
            'filters' => $filters,
            'updatedAt' => cache()->get('dashboard_updated_at'),
        ]);
    }

    private function getStores(): ?array
    {
        $cached = cache()->get('dashboard_data');
        if ($cached) {
            return $cached;
        }
        $controller = app(DashboardController::class);
        $stores = $controller->fetchFromSheet();
        if ($stores !== null) {
            $controller->storeInCache($stores);
        }
        return $stores;
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

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '' || trim($value) === '0') return null;

        $formats = ['d/m/Y', 'Y-m-d', 'm/d/Y', 'Y/m/d', 'd-m-Y', 'm-d-Y'];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, trim($value));
                if ($date !== false) return $date;
            } catch (\Exception $e) {
                continue;
            }
        }

        try {
            $date = Carbon::parse(trim($value));
            if ($date->year > 2000) return $date;
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    private function calculateKpis(array $allStores, array $filtered): array
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
