<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\TiendaRepositoryInterface;
use App\Servicios\ServicioPostgresql;
use Illuminate\Http\Request;

class ConnectivityController extends Controller
{
    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET',
    ];

    public function __construct(
        private TiendaRepositoryInterface $tiendaRepository,
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
            'tienda_salud' => $request->query('tienda_salud', ''),
        ];

        return view('connectivity', [
            'updatedAt' => now()->toDateTimeString(),
        ]);
    }
}
