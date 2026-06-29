<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\TiendaRepositoryInterface;
use App\Servicios\ServicioGeo;
use App\Servicios\ServicioPostgresql;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MapaController extends Controller
{
    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Estado', 'Nombre_UniOpe', 'Nombre_Regional',
        'Latitud', 'Longitud', 'Vta_Mes', 'Cap_Tot',
    ];

    public function __construct(
        private TiendaRepositoryInterface $tiendaRepository,
        private ServicioGeo $geo,
        private ServicioPostgresql $postgres,
    ) {}

    public function index(Request $request)
    {
        $regionFilter = $this->applyRegionFilter();
        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'estado_geo' => $request->query('estado_geo', ''),
            'tienda_salud' => $request->query('tienda_salud', ''),
        ];

        $allStores = $this->postgres->obtenerMapa($regionFilter, ['tienda_salud' => $filters['tienda_salud']], self::COLUMNS);
        $filtered = $this->postgres->obtenerMapa($regionFilter, $filters, self::COLUMNS);
        $geoLabels = ServicioGeo::GEO_LABELS;
        $geoLabels['FUERA_ESTADO']['label'] = $this->geoMismatchLabel($allStores, $regionFilter);

        $criticalesAll = collect($filtered)->filter(function ($s) {
            return ($s['_geo']['status'] ?? 'OK') !== 'OK';
        })->values()->all();
        $criticalesPagination = $this->paginateArray($criticalesAll);

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
            'tienda_salud' => $request->query('tienda_salud', ''),
        ];

        $bounds = $request->only(['north', 'south', 'east', 'west']);
        $north = (float) ($bounds['north'] ?? 90);
        $south = (float) ($bounds['south'] ?? -90);
        $east = (float) ($bounds['east'] ?? 180);
        $west = (float) ($bounds['west'] ?? -180);

        $cacheKey = 'mapa_viewport_'.md5(json_encode($regionFilter).json_encode($filters));

        $allStoresForViewport = Cache::remember($cacheKey, 60, function () use ($regionFilter, $filters) {
            return $this->postgres->obtenerMapaViewport(
                $regionFilter,
                $filters,
                ['north' => 90, 'south' => -90, 'east' => 180, 'west' => -180],
                self::COLUMNS,
            );
        });

        $skipBounds = in_array($filters['estado_geo'] ?? '', ['FUERA_MEXICO', 'INCIDENCIAS'], true);
        if ($skipBounds) {
            $filtered = $allStoresForViewport;
        } else {
            $filtered = array_values(array_filter($allStoresForViewport, function ($store) use ($north, $south, $east, $west) {
                $lat = (float) ($store['Latitud'] ?? 0);
                $lng = (float) ($store['Longitud'] ?? 0);

                return $lat >= $south && $lat <= $north && $lng >= $west && $lng <= $east;
            }));
        }

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
