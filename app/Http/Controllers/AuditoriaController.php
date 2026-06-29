<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\TiendaRepositoryInterface;
use App\Servicios\ServicioPostgresql;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    private const COLUMNS = [
        'Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio', 'Vigencia', 'Imp_Res_Audi_Mes',
        'Cap_Dic', 'Vta_Mes', 'Fch_Audit', 'Audit_Realiza_Mes', 'Asam_Real_Mes',
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
            'estado_comite' => $request->query('estado_comite', ''),
            'estado_auditoria' => $request->query('estado_auditoria', ''),
            'filtro_500k' => $request->query('filtro_500k', ''),
            'rango_rotacion' => $request->query('rango_rotacion', ''),
            'tiempo_auditoria' => $request->query('tiempo_auditoria', ''),
            'asambleas_mes' => $request->query('asambleas_mes', ''),
            'tienda_salud' => $request->query('tienda_salud', ''),
        ];

        [$page, $perPage] = $this->paginationInput();
        $sortableColumns = array_merge(self::COLUMNS, ['Comite', 'Estado_Aud', 'Rotacion', 'Riesgo']);
        $sort = $this->tableSortInput($sortableColumns);
        $result = $this->postgres->obtenerAuditoriaPaginada($this->applyRegionFilter(), $filters, $page, $perPage, self::COLUMNS, $sort);

        return view('auditoria', [
            'stores' => $result['rows'],
            'totalCount' => $result['total'],
            'filteredCount' => $result['filtered'],
            'serverPagination' => $this->paginationMeta($page, $perPage, $result['filtered']),
            'kpis' => $result['kpis'],
            'filters' => $filters,
            'sort' => $sort,
            'updatedAt' => now()->toDateTimeString(),
        ]);
    }
}
