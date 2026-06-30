<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioAlcanceUsuario;
use App\Servicios\ServicioPostgresql;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    private const COLUMNS = [
        'Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio', 'Vigencia', 'Imp_Res_Audi_Mes',
        'Cap_Dic', 'Vta_Mes', 'Fch_Audit', 'Audit_Realiza_Mes', 'Asam_Real_Mes',
    ];

    public function __construct(
        ServicioAlcanceUsuario $alcanceUsuario,
        private ServicioPostgresql $postgres,
    ) {
        parent::__construct($alcanceUsuario);
    }

    public function index(Request $request)
    {
        $filters = $this->filtersFromRequest($request, [
            'almacen', 'nivel', 'estado_comite', 'estado_auditoria', 'filtro_500k',
            'rango_rotacion', 'tiempo_auditoria', 'asambleas_mes', 'tienda_salud',
        ]);

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
        ]);
    }
}
