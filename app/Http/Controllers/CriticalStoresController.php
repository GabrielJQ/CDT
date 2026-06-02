<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioGoogleSheet;
use App\Servicios\ServicioTiendaCritica;
use App\Servicios\ServicioFiltro;
use Illuminate\Http\Request;

class CriticalStoresController extends Controller
{
    public function __construct(
        private ServicioGoogleSheet $sheet,
        private ServicioTiendaCritica $critica,
        private ServicioFiltro $filtro,
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
            'nivel' => $request->query('nivel', ''),
        ];

        $evaluated = collect($stores)->map(function ($store) {
            return array_merge($store, ['_critico' => $this->critica->evaluarTienda($store)]);
        });

        $filtered = $evaluated->filter(function ($store) use ($filters) {
            if ($filters['almacen'] !== '') {
                $nombre = $store['Nombre_Almacen'] ?? '';
                if (!str_contains(mb_strtoupper($nombre), mb_strtoupper($filters['almacen']))) {
                    return false;
                }
            }
            if ($filters['nivel'] !== '' && $store['_critico']['level'] !== $filters['nivel']) {
                return false;
            }
            return true;
        })->values()->all();

        return view('critical-stores', [
            'stores' => $filtered,
            'totalCount' => $totalCount,
            'filteredCount' => count($filtered),
            'summary' => $this->critica->calcularResumen($evaluated->all()),
            'filters' => $filters,
            'updatedAt' => cache()->get('dashboard_updated_at'),
        ]);
    }

    private function errorView(string $message)
    {
        $filters = ['almacen' => '', 'nivel' => ''];
        return view('critical-stores', [
            'stores' => [],
            'totalCount' => 0,
            'filteredCount' => 0,
            'summary' => ['rojo' => 0, 'amarillo' => 0, 'verde' => 0, 'desglose' => []],
            'filters' => $filters,
            'error' => $this->sheet->getUltimoError() ?? $message,
            'updatedAt' => null,
        ]);
    }
}
