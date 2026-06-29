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

            return ServicioExportacion::download(
                new MapaExport($this->cxc, $uoFilter),
            );
        } catch (\Throwable $e) {
            Log::error('[Export CxC Mapa] '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return back()->with('error', 'Error al exportar: '.$e->getMessage());
        }
    }
}
