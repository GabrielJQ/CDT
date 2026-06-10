<?php

namespace App\Http\Controllers;

use App\Servicios\ServicioAuditoria;
use App\Servicios\ServicioExportacion;
use App\Servicios\ServicioFiltro;
use App\Servicios\ServicioGoogleSheet;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function __construct(
        private ServicioGoogleSheet $sheet,
        private ServicioAuditoria $auditoria,
        private ServicioFiltro $filtro,
    ) {}

    public function index(Request $request)
    {
        $stores = $this->applyRegionFilter($this->sheet->obtenerTiendas());
        $totalCount = count($stores);

        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'nivel' => $request->query('nivel', ''),
            'estado_comite' => $request->query('estado_comite', ''),
            'estado_auditoria' => $request->query('estado_auditoria', ''),
            'filtro_500k' => $request->query('filtro_500k', ''),
            'rango_rotacion' => $request->query('rango_rotacion', ''),
            'tiempo_auditoria' => $request->query('tiempo_auditoria', ''),
            'asambleas_mes' => $request->query('asambleas_mes', ''),
        ];

        $evaluated = collect($stores)->map(function ($store) {
            return array_merge($store, ['_audit' => $this->auditoria->evaluarTienda($store)]);
        });

        $filtered = $evaluated->filter(function ($store) use ($filters) {
            if ($filters['almacen'] !== '') {
                $nombre = $store['Nombre_Almacen'] ?? '';
                if (! str_contains(mb_strtoupper($nombre), mb_strtoupper($filters['almacen']))) {
                    return false;
                }
            }
            if ($filters['nivel'] !== '' && ($store['_audit']['level'] ?? '') !== $filters['nivel']) {
                return false;
            }
            if ($filters['estado_comite'] !== '' && ($store['_audit']['estadoComite'] ?? '') !== $filters['estado_comite']) {
                return false;
            }
            if ($filters['estado_auditoria'] !== '') {
                $fch = $store['_audit']['fchAudit'] ?? null;
                $meses = $store['_audit']['mesesSinAuditoria'] ?? null;
                $estado = $fch ? ($meses >= 3 ? 'vencida' : 'al_dia') : 'sin_fecha';
                if ($estado !== $filters['estado_auditoria']) {
                    return false;
                }
            }
            if ($filters['filtro_500k'] !== '') {
                $impuesto = $store['_audit']['impuesto'] ?? 0;
                $esAlto = $impuesto > 500000;
                if (($filters['filtro_500k'] === 'si' && ! $esAlto) || ($filters['filtro_500k'] === 'no' && $esAlto)) {
                    return false;
                }
            }
            if ($filters['rango_rotacion'] !== '') {
                $rango = $store['_audit']['rangoRotacion'] ?? '';
                if ($rango !== $filters['rango_rotacion']) {
                    return false;
                }
            }
            if ($filters['tiempo_auditoria'] !== '') {
                $auditRealizada = $store['_audit']['auditRealizada'] ?? 0;
                $sinAuditoriaAnio = $store['_audit']['sinAuditoriaAnio'] ?? false;
                $fchAudit = $store['_audit']['fchAudit'] ?? null;
                $mesesSinAuditoria = $store['_audit']['mesesSinAuditoria'] ?? null;
                $sinAuditoriaTrimestre = $fchAudit === null || ($mesesSinAuditoria !== null && $mesesSinAuditoria >= 3);

                if ($filters['tiempo_auditoria'] === 'mes' && $auditRealizada == 0) {
                    return false;
                }
                if ($filters['tiempo_auditoria'] === 'trimestre' && ! $sinAuditoriaTrimestre) {
                    return false;
                }
                if ($filters['tiempo_auditoria'] === 'anio' && ! $sinAuditoriaAnio) {
                    return false;
                }
            }
            if ($filters['asambleas_mes'] !== '') {
                $asambleas = (int) ($store['Asam_Real_Mes'] ?? 0);
                if ($filters['asambleas_mes'] === 'si' && $asambleas == 0) {
                    return false;
                }
                if ($filters['asambleas_mes'] === 'no' && $asambleas > 0) {
                    return false;
                }
            }

            return true;
        })->values()->all();

        if ($request->query('export') === 'csv') {
            return ServicioExportacion::csvResponse($filtered, [
                'Nombre_Almacen' => 'Almacén',
                'No_Tienda_Actual' => 'Tienda #',
                'Localidad' => 'Localidad',
                'Municipio' => 'Municipio',
                'Vigencia' => 'Vigencia',
                '_audit.estadoComite' => 'Estado Comité',
                '_audit.fchAudit' => 'Fch Auditoría',
                '_audit.mesesSinAuditoria' => 'Meses Sin Auditoría',
                '_audit.impuesto' => 'Impuesto',
                '_audit.rotacion' => 'Rotación',
                '_audit.level' => 'Nivel Riesgo',
            ], 'auditoria.csv');
        }

        return view('auditoria', [
            'stores' => $filtered,
            'totalCount' => $totalCount,
            'filteredCount' => count($filtered),
            'kpis' => $this->auditoria->calcularKpis($evaluated->all()),
            'filters' => $filters,
            'updatedAt' => cache()->get('dashboard_updated_at'),
        ]);
    }
}
