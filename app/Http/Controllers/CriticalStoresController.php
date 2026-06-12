<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioPostgresql;
use Illuminate\Http\Request;

class CriticalStoresController extends Controller
{
    private const COLUMNS = [
        'Estado', 'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Cap_Tot', 'Cap_Dic', 'Vigencia',
        'Imp_Res_Audi_Mes', 'Pagare_Fecha', 'Vta_Mes', 'Asam_Prog_Mes', 'Asam_Real_Mes',
    ];

    public function __construct(
        private ServicioPostgresql $postgres,
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'nivel' => $request->query('nivel', ''),
            'indicador' => $request->query('indicador', ''),
        ];

        if ($request->query('export') === 'csv') {
            $exportData = (function () use ($filters) {
                foreach ($this->postgres->exportarTiendas($this->applyRegionFilter(), $filters, self::COLUMNS, 'criticidad') as $store) {
                    $critico = $store['_critico'] ?? [];
                    $detalle = [];
                    foreach (($critico['conditions'] ?? []) as $key => $active) {
                        if ($active) {
                            $label = $critico['labels'][$key]['label'] ?? $key;
                            $detail = $critico['labels'][$key]['detail'] ?? '';
                            $detalle[] = $detail ? "$label ($detail)" : $label;
                        }
                    }
                    $store['_detalle_factores'] = implode('; ', $detalle);

                    yield $store;
                }
            })();

            return ServicioExportacion::csvStream($exportData, [
                'Nombre_Almacen' => 'Almacén',
                'No_Tienda_Actual' => 'Tienda #',
                'Municipio' => 'Municipio',
                '_critico.level' => 'Estado',
                '_critico.count' => 'Factores Activos',
                '_detalle_factores' => 'Detalle',
            ], 'informacion-tiendas.csv');
        }

        $indicadores = [
            'capital_bajo' => '💰 Capital total bajo',
            'capital_dictaminado_bajo' => '🏛️ Capital Bienestar bajo',
            'comite_vencido' => '📅 Comité vencido',
            'auditoria_elevada' => '🔍 Auditoría > $500k',
            'pagare_vencido' => '📄 Pagaré vencido',
            'rotacion_baja' => '📉 Rotación baja',
            'asamblea_pendiente' => '🗳️ Asamblea pendiente',
        ];

        [$page, $perPage] = $this->paginationInput();
        $result = $this->postgres->obtenerCriticidadPaginada($this->applyRegionFilter(), $filters, $page, $perPage, self::COLUMNS);

        return view('critical-stores', [
            'stores' => $result['rows'],
            'totalCount' => $result['total'],
            'filteredCount' => $result['filtered'],
            'serverPagination' => $this->paginationMeta($page, $perPage, $result['filtered']),
            'summary' => $result['summary'],
            'filters' => $filters,
            'indicadores' => $indicadores,
            'updatedAt' => now()->toDateTimeString(),
        ]);
    }
}
