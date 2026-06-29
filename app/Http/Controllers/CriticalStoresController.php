<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\TiendaRepositoryInterface;
use App\Servicios\ServicioPostgresql;
use Illuminate\Http\Request;

class CriticalStoresController extends Controller
{
    private const COLUMNS = [
        'Estado', 'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Cap_Tot', 'Cap_Dic', 'Vigencia',
        'Imp_Res_Audi_Mes', 'Pagare_Fecha', 'Vta_Mes', 'Asam_Prog_Mes', 'Asam_Real_Mes',
    ];

    public function __construct(
        private TiendaRepositoryInterface $tiendaRepository,
        private ServicioPostgresql $postgres,
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'nivel' => $request->query('nivel', ''),
            'indicador' => $request->query('indicador', ''),
            'tienda_salud' => $request->query('tienda_salud', ''),
        ];

        [$page, $perPage] = $this->paginationInput();
        $sortableColumns = array_merge(self::COLUMNS, ['Factores', 'Detalle']);
        $sort = $this->tableSortInput($sortableColumns);
        $result = $this->postgres->obtenerCriticidadPaginada($this->applyRegionFilter(), $filters, $page, $perPage, self::COLUMNS, $sort);

        return view('critical-stores', [
            'stores' => $result['rows'],
            'totalCount' => $result['total'],
            'filteredCount' => $result['filtered'],
            'serverPagination' => $this->paginationMeta($page, $perPage, $result['filtered']),
            'summary' => $result['summary'],
            'filters' => $filters,
            'sort' => $sort,
            'updatedAt' => now()->toDateTimeString(),
        ]);
    }
}
