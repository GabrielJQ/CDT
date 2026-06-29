<?php

namespace App\Http\Controllers\Export;

use App\Exports\ConectividadExport;
use App\Http\Controllers\Controller;
use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioPostgresql;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConectividadExportController extends Controller
{
    public function __construct(
        private ServicioPostgresql $postgres,
    ) {}

    public function download(Request $request)
    {
        try {
            $filters = [
                'almacen' => trim($request->query('almacen', '')),
                'telefono' => $request->query('telefono', ''),
                'senial' => $request->query('senial', ''),
                'compania' => $request->query('compania', ''),
                'internet' => $request->query('internet', ''),
                'tienda_salud' => $request->query('tienda_salud', ''),
            ];

            return ServicioExportacion::download(
                new ConectividadExport($this->postgres, $this->applyRegionFilter()),
                $filters,
            );
        } catch (\Throwable $e) {
            Log::error('[Export Conectividad] '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return back()->with('error', 'Error al exportar: '.$e->getMessage());
        }
    }
}
