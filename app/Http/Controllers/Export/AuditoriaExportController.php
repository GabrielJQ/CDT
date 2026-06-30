<?php

namespace App\Http\Controllers\Export;

use App\Exports\AuditoriaExport;
use App\Http\Controllers\Controller;
use App\Servicios\ServicioAlcanceUsuario;
use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioPostgresql;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditoriaExportController extends Controller
{
    public function __construct(
        ServicioAlcanceUsuario $alcanceUsuario,
        private ServicioPostgresql $postgres,
    ) {
        parent::__construct($alcanceUsuario);
    }

    public function download(Request $request)
    {
        try {
            $filters = [
                'almacen' => trim($request->query('almacen', '')),
                'nivel' => $request->query('nivel', ''),
                'estado_comite' => $request->query('estado_comite', ''),
                'estado_auditoria' => $request->query('estado_auditoria', ''),
                'filtro_500k' => $request->query('filtro_500k', ''),
                'rango_rotacion' => $request->query('rango_rotacion', ''),
                'tiempo_auditoria' => $request->query('tiempo_auditoria', ''),
                'asambleas_mes' => $request->query('asambleas_mes', ''),
                'tienda_salud' => $request->query('tienda_salud', ''),
            ];

            return ServicioExportacion::download(
                new AuditoriaExport($this->postgres, $this->applyRegionFilter()),
                $filters,
            );
        } catch (\Throwable $e) {
            Log::error('[Export Auditoria] '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return back()->with('error', 'Ocurrió un error al generar el archivo. Intente de nuevo más tarde.');
        }
    }
}
