<?php

namespace App\Http\Controllers\Export;

use App\Exports\CasaPorCasa\MapaExport;
use App\Http\Controllers\Controller;
use App\Servicios\ServicioCasaPorCasa;
use App\Servicios\ServicioExportacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CasaPorCasaMapaExportController extends Controller
{
    public function __construct(
        private ServicioCasaPorCasa $cxc,
    ) {}

    public function download(Request $request)
    {
        try {
            $uoFilter = $this->cxc->resolveUoFilter();

            $filters = [
                'almacen' => $request->query('almacen', ''),
                'estado' => $request->query('estado', ''),
                'uo' => $request->query('uo', ''),
                'estatus' => $request->query('estatus', ''),
                'anaquelStatus' => $request->query('anaquelStatus', ''),
                'buscar' => $request->query('buscar', ''),
            ];

            return ServicioExportacion::download(
                new MapaExport($this->cxc, $uoFilter),
                $filters,
            );
        } catch (\Throwable $e) {
            Log::error('[Export CxC Mapa] '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return back()->with('error', 'Ocurrió un error al generar el archivo. Intente de nuevo más tarde.');
        }
    }
}
