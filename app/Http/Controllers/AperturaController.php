<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioFecha;
use App\Servicios\ServicioPostgresql;
use Illuminate\Http\Request;

class AperturaController extends Controller
{
    private const COLUMNS = [
        'Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio', 'Fecha_Apertura',
    ];

    public function __construct(
        private ServicioFecha $fecha,
        private ServicioPostgresql $postgres,
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'desde' => $this->fecha->parsear($request->query('desde', ''))?->toDateString() ?? '',
            'hasta' => $this->fecha->parsear($request->query('hasta', ''))?->toDateString() ?? '',
        ];

        if ($request->query('export') === 'csv') {
            return ServicioExportacion::csvStream($this->postgres->exportarTiendas($this->applyRegionFilter(), $filters, self::COLUMNS, 'aperturas'), [
                'Nombre_Almacen' => 'Almacén',
                'No_Tienda_Actual' => 'Tienda #',
                'Localidad' => 'Localidad',
                'Municipio' => 'Municipio',
                'Fecha_Apertura' => 'Fecha Apertura',
                '_fecha_apertura' => 'Apertura (parseada)',
                '_antiguedad' => 'Antigüedad',
            ], 'aperturas.csv');
        }

        [$page, $perPage] = $this->paginationInput();
        $sortableColumns = array_merge(self::COLUMNS, ['_fecha_apertura', '_antiguedad']);
        $sort = $this->tableSortInput($sortableColumns);
        $result = $this->postgres->obtenerAperturasPaginada($this->applyRegionFilter(), $filters, $page, $perPage, self::COLUMNS, $sort);

        return view('aperturas', [
            'stores' => $result['rows'],
            'totalCount' => $result['total'],
            'filteredCount' => $result['filtered'],
            'serverPagination' => $this->paginationMeta($page, $perPage, $result['filtered']),
            'kpis' => $result['kpis'],
            'filters' => [
                'almacen' => $request->query('almacen', ''),
                'desde' => $request->query('desde', ''),
                'hasta' => $request->query('hasta', ''),
            ],
            'sort' => $sort,
            'updatedAt' => now()->toDateTimeString(),
        ]);
    }
}
