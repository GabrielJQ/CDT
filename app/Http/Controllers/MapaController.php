<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioGeo;
use App\Servicios\ServicioPostgresql;
use Illuminate\Http\Request;

class MapaController extends Controller
{
    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Estado', 'Nombre_UniOpe', 'Nombre_Regional',
        'Latitud', 'Longitud', 'Vta_Mes', 'Cap_Tot',
    ];

    public function __construct(
        private ServicioGeo $geo,
        private ServicioPostgresql $postgres,
    ) {}

    public function index(Request $request)
    {
        $regionFilter = $this->applyRegionFilter();
        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'estado_geo' => $request->query('estado_geo', ''),
        ];

        $allStores = $this->postgres->obtenerMapa($regionFilter, [], self::COLUMNS);
        $filtered = $this->postgres->obtenerMapa($regionFilter, $filters, self::COLUMNS);
        $geoLabels = ServicioGeo::GEO_LABELS;
        $geoLabels['FUERA_ESTADO']['label'] = $this->geoMismatchLabel($allStores, $regionFilter);

        $criticalesAll = collect($filtered)->filter(function ($s) {
            return ($s['_geo']['status'] ?? 'OK') !== 'OK';
        })->values()->all();
        $criticalesPagination = $this->paginateArray($criticalesAll);

        if ($request->query('export') === 'csv') {
            return ServicioExportacion::csvStream($this->postgres->exportarTiendas($regionFilter, $filters, self::COLUMNS, 'mapa'), [
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
            'stores' => [],
            'criticales' => $criticalesPagination['items'],
            'criticalesTotal' => count($criticalesAll),
            'serverPagination' => $criticalesPagination['meta'],
            'totalCount' => count($allStores),
            'filteredCount' => count($filtered),
            'stats' => $this->geo->calcularStats($allStores),
            'filters' => $filters,
            'geoLabels' => $geoLabels,
            'geoMismatchLabel' => $geoLabels['FUERA_ESTADO']['label'],
            'updatedAt' => now()->toDateTimeString(),
        ]);
    }

    public function data(Request $request)
    {
        $regionFilter = $this->applyRegionFilter();
        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'estado_geo' => $request->query('estado_geo', ''),
        ];

        $filtered = $this->postgres->obtenerMapaViewport(
            $regionFilter,
            $filters,
            $request->only(['north', 'south', 'east', 'west']),
            self::COLUMNS,
        );

        return response()->json(['stores' => $filtered, 'limited' => count($filtered) >= 3000]);
    }

    private function geoMismatchLabel(array $stores, array $regionFilter): string
    {
        if (! empty($regionFilter['uo'])) {
            $uoName = $this->firstNonEmptyValue($stores, 'Nombre_UniOpe');

            return $uoName !== '' ? 'No corresponde a '.$uoName : 'No corresponde a la UO filtrada';
        }

        if (! empty($regionFilter['region'])) {
            $regionName = $this->firstNonEmptyValue($stores, 'Nombre_Regional');

            return $regionName !== '' ? 'No corresponde a '.$regionName : 'No corresponde a la region filtrada';
        }

        return 'No corresponde al estado registrado';
    }

    private function firstNonEmptyValue(array $stores, string $key): string
    {
        foreach ($stores as $store) {
            $value = trim((string) ($store[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
