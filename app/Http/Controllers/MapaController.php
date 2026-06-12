<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioFiltro;
use App\Servicios\ServicioGeo;
use App\Servicios\ServicioGoogleSheet;
use Illuminate\Http\Request;

class MapaController extends Controller
{
    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Estado', 'Nombre_UniOpe', 'Nombre_Regional',
        'Latitud', 'Longitud', 'Vta_Mes', 'Cap_Tot',
    ];

    public function __construct(
        private ServicioGoogleSheet $sheet,
        private ServicioGeo $geo,
        private ServicioFiltro $filtro,
    ) {}

    public function index(Request $request)
    {
        $regionFilter = $this->applyRegionFilter();
        $stores = $this->sheet->obtenerTiendas($regionFilter, self::COLUMNS);
        $totalCount = count($stores);
        $geoLabels = ServicioGeo::GEO_LABELS;
        $geoLabels['FUERA_ESTADO']['label'] = $this->geoMismatchLabel($stores, $regionFilter);

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
                if (! str_contains(mb_strtoupper($nombre), mb_strtoupper($filters['almacen']))) {
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
            'stores' => [],
            'criticales' => $criticales,
            'totalCount' => $totalCount,
            'filteredCount' => count($filtered),
            'stats' => $this->geo->calcularStats($evaluated->all()),
            'filters' => $filters,
            'geoLabels' => $geoLabels,
            'geoMismatchLabel' => $geoLabels['FUERA_ESTADO']['label'],
            'updatedAt' => now()->toDateTimeString(),
        ]);
    }

    public function data(Request $request)
    {
        $regionFilter = $this->applyRegionFilter();
        $stores = $this->sheet->obtenerTiendas($regionFilter, self::COLUMNS);
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
                if (! str_contains(mb_strtoupper($nombre), mb_strtoupper($filters['almacen']))) {
                    return false;
                }
            }
            if ($filters['estado_geo'] !== '' && ($store['_geo']['status'] ?? '') !== $filters['estado_geo']) {
                return false;
            }

            return true;
        })->values()->all();

        return response()->json(['stores' => $filtered]);
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
