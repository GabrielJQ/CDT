<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioGoogleSheet;
use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioGeo;
use App\Servicios\ServicioFiltro;
use Illuminate\Http\Request;

class MapaController extends Controller
{
    public function __construct(
        private ServicioGoogleSheet $sheet,
        private ServicioGeo $geo,
        private ServicioFiltro $filtro,
    ) {}

    public function index(Request $request)
    {
        $stores = $this->sheet->obtenerTiendas();
        if ($stores === null) {
            return $this->errorView();
        }

        $stores = $this->applyRegionFilter($stores);
        $totalCount = count($stores);

        $evaluated = collect($stores)->map(function ($store) {
            $store['_geo'] = $this->geo->evaluarGeo($store);
            return $store;
        });

        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'estado_geo' => $request->query('estado_geo', ''),
        ];

        $filtered = $evaluated->filter(function ($store) use ($filters) {
            if ($filters['almacen'] !== '') {
                $nombre = $store['Nombre_Almacen'] ?? '';
                if (!str_contains(mb_strtoupper($nombre), mb_strtoupper($filters['almacen']))) {
                    return false;
                }
            }
            if ($filters['estado_geo'] !== '' && ($store['_geo']['status'] ?? '') !== $filters['estado_geo']) {
                return false;
            }
            return true;
        })->values()->all();

        $criticales = collect($filtered)->filter(function ($s) {
            return ($s['_geo']['status'] ?? 'OK') !== 'OK';
        })->values()->all();

        if ($request->query('export') === 'csv') {
            return ServicioExportacion::csvResponse($filtered, [
                'Nombre_Almacen' => 'Almacén',
                'No_Tienda_Actual' => 'Tienda #',
                'Municipio' => 'Municipio',
                'Estado' => 'Estado',
                '_geo.lat' => 'Latitud',
                '_geo.lon' => 'Longitud',
                '_geo.status' => 'Estatus Geo',
                '_geo.mensaje' => 'Mensaje',
            ], 'mapa.csv');
        }

        return view('mapa', [
            'stores' => $filtered,
            'criticales' => $criticales,
            'totalCount' => $totalCount,
            'filteredCount' => count($filtered),
            'stats' => $this->geo->calcularStats($evaluated->all()),
            'filters' => $filters,
            'geoLabels' => ServicioGeo::GEO_LABELS,
            'updatedAt' => cache()->get('dashboard_updated_at'),
        ]);
    }

    private function errorView()
    {
        return view('mapa', [
            'stores' => [],
            'criticales' => [],
            'totalCount' => 0,
            'filteredCount' => 0,
            'stats' => ['OK' => 0, 'SIN_COORDENADAS' => 0, 'FUERA_MEXICO' => 0, 'FUERA_ESTADO' => 0],
            'filters' => ['almacen' => '', 'estado_geo' => ''],
            'geoLabels' => ServicioGeo::GEO_LABELS,
            'error' => $this->sheet->getUltimoError() ?? 'No se pudieron obtener los datos del Google Sheet.',
            'updatedAt' => null,
        ]);
    }
}
