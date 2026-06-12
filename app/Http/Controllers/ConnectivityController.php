<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioConectividad;
use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioFiltro;
use App\Servicios\ServicioGoogleSheet;
use Illuminate\Http\Request;

class ConnectivityController extends Controller
{
    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET',
    ];

    public function __construct(
        private ServicioGoogleSheet $sheet,
        private ServicioConectividad $conectividad,
        private ServicioFiltro $filtro,
    ) {}

    public function index(Request $request)
    {
        $stores = $this->sheet->obtenerTiendas($this->applyRegionFilter(), self::COLUMNS);
        $totalCount = count($stores);

        $filterOptions = [
            'almacenes' => $this->filtro->opcionesAlmacen($stores),
            'companias' => $this->filtro->opcionesCompania($stores),
        ];

        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'telefono' => $request->query('telefono', ''),
            'senial' => $request->query('senial', ''),
            'compania' => $request->query('compania', ''),
            'internet' => $request->query('internet', ''),
        ];

        $filtered = collect($stores)->filter(function ($store) use ($filters) {
            if ($filters['almacen'] !== '') {
                $nombre = $store['Nombre_Almacen'] ?? '';
                if (! str_contains(mb_strtoupper($nombre), mb_strtoupper($filters['almacen']))) {
                    return false;
                }
            }
            if ($filters['telefono'] === 'si' && (strtoupper(trim($store['TELEFONIA'] ?? '')) !== 'S')) {
                return false;
            }
            if ($filters['telefono'] === 'no' && (strtoupper(trim($store['TELEFONIA'] ?? '')) !== 'N')) {
                return false;
            }
            if ($filters['senial'] === 'si' && (strtoupper(trim($store['Señal de celular'] ?? '')) !== 'S')) {
                return false;
            }
            if ($filters['senial'] === 'no' && (strtoupper(trim($store['Señal de celular'] ?? '')) !== 'N')) {
                return false;
            }
            if ($filters['internet'] === 'si' && (strtoupper(trim($store['INTERNET'] ?? '')) !== 'S')) {
                return false;
            }
            if ($filters['internet'] === 'no' && (strtoupper(trim($store['INTERNET'] ?? '')) !== 'N')) {
                return false;
            }
            if ($filters['compania'] !== '') {
                $comp = strtoupper(trim($store['Compañía'] ?? ''));
                $filterComp = strtoupper(trim($filters['compania']));
                if ($filterComp === 'SIN DATO' || $filterComp === 'SIN_DATO') {
                    if ($comp !== '' && $comp !== 'SIN DATO' && $comp !== 'NINGUNO') {
                        return false;
                    }
                } elseif ($comp !== $filterComp) {
                    return false;
                }
            }

            return true;
        })->values()->all();

        if ($request->query('export') === 'csv') {
            return ServicioExportacion::csvResponse($filtered, [
                'Nombre_Almacen' => 'Almacén',
                'No_Tienda_Actual' => 'Tienda #',
                'Municipio' => 'Municipio',
                'TELEFONIA' => 'Teléfono',
                'Señal de celular' => 'Señal Celular',
                'Compañía' => 'Compañía',
                'INTERNET' => 'Internet',
            ], 'conectividad.csv');
        }

        $pagination = $this->paginateArray($filtered);

        return view('connectivity', [
            'kpis' => $this->conectividad->calcularKpis($filtered),
            'stores' => $pagination['items'],
            'totalCount' => $totalCount,
            'filteredCount' => count($filtered),
            'serverPagination' => $pagination['meta'],
            'filterOptions' => $filterOptions,
            'filters' => $filters,
            'updatedAt' => now()->toDateTimeString(),
        ]);
    }
}
