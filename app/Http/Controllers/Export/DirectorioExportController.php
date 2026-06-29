<?php

namespace App\Http\Controllers\Export;

use App\Exports\DirectorioExport;
use App\Http\Controllers\Controller;
use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioPostgresql;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DirectorioExportController extends Controller
{
    public function __construct(
        private ServicioPostgresql $postgres,
    ) {}

    public function download(Request $request)
    {
        try {
            $filters = [
                'q' => trim($request->query('q', '')),
                'incompletos' => $request->boolean('incompletos'),
                'sinCapital' => $request->boolean('sinCapital'),
                'tienda_salud' => $request->query('tienda_salud', ''),
            ];

            return ServicioExportacion::download(
                new DirectorioExport($this->postgres, $this->applyRegionFilter()),
                $filters,
            );
        } catch (\Throwable $e) {
            Log::error('[Export Directorio] '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return back()->with('error', 'Error al exportar: '.$e->getMessage());
        }
    }
}
