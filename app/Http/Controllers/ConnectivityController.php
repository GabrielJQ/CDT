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

        return view('connectivity', [
            'updatedAt' => now()->toDateTimeString(),
        ]);
    }
}
