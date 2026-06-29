<?php

namespace App\Http\Controllers\Export;

use App\Exports\CriticidadExport;
use App\Http\Controllers\Controller;
use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioPostgresql;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CriticidadExportController extends Controller
{
    public function __construct(
        private ServicioPostgresql $postgres,
    ) {}

    public function download(Request $request)
    {
        try {
            $filters = [
                'almacen' => trim($request->query('almacen', '')),
                'nivel' => $request->query('nivel', ''),
                'indicador' => $request->query('indicador', ''),
                'tienda_salud' => $request->query('tienda_salud', ''),
            ];

            return ServicioExportacion::download(
                new CriticidadExport($this->postgres, $this->applyRegionFilter()),
                $filters,
            );
        } catch (\Throwable $e) {
            Log::error('[Export Criticidad] '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return back()->with('error', 'Error al exportar: '.$e->getMessage());
        }
    }
}
