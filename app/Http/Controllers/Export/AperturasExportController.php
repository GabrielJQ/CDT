<?php

namespace App\Http\Controllers\Export;

use App\Exports\AperturasExport;
use App\Http\Controllers\Controller;
use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioFecha;
use App\Servicios\ServicioPostgresql;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AperturasExportController extends Controller
{
    public function __construct(
        private ServicioPostgresql $postgres,
        private ServicioFecha $fecha,
    ) {}

    public function download(Request $request)
    {
        try {
            $filters = [
                'almacen' => trim($request->query('almacen', '')),
                'desde' => $this->fecha->parsear($request->query('desde', ''))?->toDateString() ?? '',
                'hasta' => $this->fecha->parsear($request->query('hasta', ''))?->toDateString() ?? '',
                'tienda_salud' => $request->query('tienda_salud', ''),
            ];

            return ServicioExportacion::download(
                new AperturasExport($this->postgres, $this->applyRegionFilter()),
                $filters,
            );
        } catch (\Throwable $e) {
            Log::error('[Export Aperturas] '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return back()->with('error', 'Error al exportar: '.$e->getMessage());
        }
    }
}
