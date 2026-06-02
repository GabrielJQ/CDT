<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioGoogleSheet;
use App\Servicios\ServicioTiendaCritica;
use App\Servicios\ServicioExportacion;
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
            abort(503, $this->sheet->getUltimoError() ?? 'No se pudieron obtener los datos del Google Sheet.');
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

        if ($request->query('export') === 'csv') {
            $exportData = collect($filtered)->map(function ($store) {
                $critico = $store['_critico'] ?? [];
                $detalle = [];
                foreach (($critico['conditions'] ?? []) as $key => $active) {
                    if ($active) {
                        $label = $critico['labels'][$key]['label'] ?? $key;
                        $detail = $critico['labels'][$key]['detail'] ?? '';
                        $detalle[] = $detail ? "$label ($detail)" : $label;
                    }
                }
                $store['_detalle_factores'] = implode('; ', $detalle);
                return $store;
            })->all();

            return ServicioExportacion::csvResponse($exportData, [
                'Nombre_Almacen' => 'Almacén',
                'No_Tienda_Actual' => 'Tienda #',
                'Municipio' => 'Municipio',
                '_critico.level' => 'Estado',
                '_critico.count' => 'Factores Activos',
                '_detalle_factores' => 'Detalle',
            ], 'informacion-tiendas.csv');
        }

        return view('critical-stores', [
            'stores' => $filtered,
            'totalCount' => $totalCount,
            'filteredCount' => count($filtered),
            'summary' => $this->critica->calcularResumen($evaluated->all()),
            'filters' => $filters,
            'updatedAt' => cache()->get('dashboard_updated_at'),
        ]);
    }


}
