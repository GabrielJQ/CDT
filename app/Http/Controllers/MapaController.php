<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioAlcanceUsuario;
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
        ServicioAlcanceUsuario $alcanceUsuario,
        private ServicioGeo $geo,
        private ServicioPostgresql $postgres,
    ) {
        parent::__construct($alcanceUsuario);
    }

    public function index(Request $request)
    {
        $regionFilter = $this->applyRegionFilter();
        $filters = $this->filtersFromRequest($request, ['almacen', 'estado_geo', 'tienda_salud']);

        $allStores = $this->postgres->obtenerMapa($regionFilter, ['tienda_salud' => $filters['tienda_salud']], self::COLUMNS);
        $filtered = $this->postgres->obtenerMapa($regionFilter, $filters, self::COLUMNS);

        $criticalesAll = $this->geo->filtrarCriticos($filtered);
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
            'geoLabels' => ServicioGeo::GEO_LABELS,
            'geoMismatchLabel' => $this->geo->geoMismatchLabel($allStores, $regionFilter),
        ]);
    }

    public function data(Request $request)
    {
        $regionFilter = $this->applyRegionFilter();
        $filters = $this->filtersFromRequest($request, ['almacen', 'estado_geo', 'tienda_salud']);

        $bounds = $request->only(['north', 'south', 'east', 'west']);
        $north = (float) ($bounds['north'] ?? 90);
        $south = (float) ($bounds['south'] ?? -90);
        $east = (float) ($bounds['east'] ?? 180);
        $west = (float) ($bounds['west'] ?? -180);

        $cacheKey = 'mapa_viewport_'.md5(json_encode($regionFilter).json_encode($filters));

        $allStoresForViewport = Cache::remember($cacheKey, 60, function () use ($regionFilter, $filters) {
            return $this->postgres->obtenerMapaViewport(
                $regionFilter, $filters,
                ['north' => 90, 'south' => -90, 'east' => 180, 'west' => -180],
                self::COLUMNS,
            );
        });

        $skipBounds = $this->geo->skipBounds($filters['estado_geo'] ?? '');
        $filtered = $skipBounds
            ? $allStoresForViewport
            : $this->geo->filtrarPorViewport($allStoresForViewport, $north, $south, $east, $west);

        return response()->json(['stores' => $filtered, 'limited' => count($filtered) >= 3000]);
    }
}
