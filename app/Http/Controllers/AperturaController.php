<?php

namespace App\Http\Controllers;

use App\Contracts\Repositories\TiendaRepositoryInterface;
use App\Servicios\ServicioFecha;
use App\Servicios\ServicioPostgresql;
use Illuminate\Http\Request;

class AperturaController extends Controller
{
    private const COLUMNS = [
        'Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio', 'Fecha_Apertura',
    ];

    public function __construct(
        private TiendaRepositoryInterface $tiendaRepository,
        private ServicioFecha $fecha,
        private ServicioPostgresql $postgres,
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'desde' => $this->fecha->parsear($request->query('desde', ''))?->toDateString() ?? '',
            'hasta' => $this->fecha->parsear($request->query('hasta', ''))?->toDateString() ?? '',
            'tienda_salud' => $request->query('tienda_salud', ''),
        ];

        return view('aperturas', [
            'updatedAt' => now()->toDateTimeString(),
        ]);
    }
}
