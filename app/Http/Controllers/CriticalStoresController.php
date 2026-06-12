<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioFiltro;
use App\Servicios\ServicioGoogleSheet;
use App\Servicios\ServicioTiendaCritica;
use Illuminate\Http\Request;

class CriticalStoresController extends Controller
{
    private const COLUMNS = [
        'Estado', 'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Cap_Tot', 'Cap_Dic', 'Vigencia',
        'Imp_Res_Audi_Mes', 'Pagare_Fecha', 'Vta_Mes', 'Asam_Prog_Mes', 'Asam_Real_Mes',
    ];

    public function __construct(
        private ServicioGoogleSheet $sheet,
        private ServicioTiendaCritica $critica,
        private ServicioFiltro $filtro,
    ) {}

    public function index(Request $request)
    {
        $stores = $this->sheet->obtenerTiendas($this->applyRegionFilter(), self::COLUMNS);
        $totalCount = count($stores);

        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'nivel' => $request->query('nivel', ''),
            'indicador' => $request->query('indicador', ''),
        ];

        $evaluated = collect($stores)->map(function ($store) {
            return array_merge($store, ['_critico' => $this->critica->evaluarTienda($store)]);
        });

        $filtered = $evaluated->filter(function ($store) use ($filters) {
            if ($filters['almacen'] !== '') {
                $nombre = $store['Nombre_Almacen'] ?? '';
                if (! str_contains(mb_strtoupper($nombre), mb_strtoupper($filters['almacen']))) {
                    return false;
                }
            }
            if ($filters['nivel'] !== '' && $store['_critico']['level'] !== $filters['nivel']) {
                return false;
            }
            if ($filters['indicador'] !== '') {
                $active = $store['_critico']['conditions'][$filters['indicador']] ?? false;
                if (! $active) {
                    return false;
                }
            }

            return true;
        })->values()->all();

        if ($request->query('export') === 'csv') {
            $exportData = collect($filtered)->map(function ($store) {
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

                return $store;
            })->all();

            return ServicioExportacion::csvResponse($exportData, [
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

        $pagination = $this->paginateArray($filtered);

        return view('critical-stores', [
            'stores' => $pagination['items'],
            'totalCount' => $totalCount,
            'filteredCount' => count($filtered),
            'serverPagination' => $pagination['meta'],
            'summary' => $this->critica->calcularResumen($evaluated->all()),
            'filters' => $filters,
            'indicadores' => $indicadores,
            'updatedAt' => now()->toDateTimeString(),
        ]);
    }
}
