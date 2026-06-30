<?php

namespace App\Http\Controllers\Export;

use App\Exports\CasaPorCasa\DirectorioExport;
use App\Http\Controllers\Controller;
use App\Servicios\ServicioCasaPorCasa;
use App\Servicios\ServicioExportacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CasaPorCasaDirectorioExportController extends Controller
{
    public function __construct(
        private ServicioCasaPorCasa $cxc,
    ) {}

    public function download(Request $request)
    {
        try {
            $uoFilter = $this->cxc->resolveUoFilter();
            $filters = [
                'estado' => $request->query('estado', ''),
                'uo' => $request->query('uo', ''),
                'estatus' => $request->query('estatus', ''),
                'buscar' => $request->query('buscar', ''),
            ];

            return ServicioExportacion::download(
                new DirectorioExport($this->cxc, $uoFilter),
                $filters,
            );
        } catch (\Throwable $e) {
            Log::error('[Export CxC Directorio] '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return back()->with('error', 'Ocurrió un error al generar el archivo. Intente de nuevo más tarde.');
        }
    }
}
