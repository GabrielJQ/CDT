<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioPostgresql;
use Illuminate\Http\Request;

class ConnectivityController extends Controller
{
    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET',
    ];

    public function __construct(
        private ServicioPostgresql $postgres,
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'telefono' => $request->query('telefono', ''),
            'senial' => $request->query('senial', ''),
            'compania' => $request->query('compania', ''),
            'internet' => $request->query('internet', ''),
        ];

        if ($request->query('export') === 'csv') {
            return ServicioExportacion::csvStream($this->postgres->exportarTiendas($this->applyRegionFilter(), $filters, self::COLUMNS, 'conectividad'), [
                'Nombre_Almacen' => 'Almacén',
                'No_Tienda_Actual' => 'Tienda #',
                'Municipio' => 'Municipio',
                'TELEFONIA' => 'Teléfono',
                'Señal de celular' => 'Señal Celular',
                'Compañía' => 'Compañía',
                'INTERNET' => 'Internet',
            ], 'conectividad.csv');
        }

        [$page, $perPage] = $this->paginationInput();
        $result = $this->postgres->obtenerConectividadPaginada($this->applyRegionFilter(), $filters, $page, $perPage);

        return view('connectivity', [
            'kpis' => $result['kpis'],
            'stores' => $result['rows'],
            'totalCount' => $result['total'],
            'filteredCount' => $result['filtered'],
            'serverPagination' => $this->paginationMeta($page, $perPage, $result['filtered']),
            'filterOptions' => [
                'almacenes' => [],
                'companias' => $result['companias'],
            ],
            'filters' => $filters,
            'updatedAt' => now()->toDateTimeString(),
        ]);
    }
}
