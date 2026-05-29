<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $stores = $this->getStores();
        if ($stores === null) {
            return $this->errorView();
        }

        $stores = $this->applyRegionFilter($stores);
        $totalCount = count($stores);

        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'nivel' => $request->query('nivel', ''),
            'estado_comite' => $request->query('estado_comite', ''),
            'estado_auditoria' => $request->query('estado_auditoria', ''),
            'filtro_500k' => $request->query('filtro_500k', ''),
        ];

        $evaluated = collect($stores)->map(function ($store) {
            $audit = $this->evaluateAudit($store);
            return array_merge($store, ['_audit' => $audit]);
        });

        $filtered = $evaluated->filter(function ($store) use ($filters) {
            if ($filters['almacen'] !== '') {
                $nombre = $store['Nombre_Almacen'] ?? '';
                if (!str_contains(mb_strtoupper($nombre), mb_strtoupper($filters['almacen']))) {
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
                if (($filters['filtro_500k'] === 'si' && !$esAlto) || ($filters['filtro_500k'] === 'no' && $esAlto)) {
                    return false;
                }
            }
            return true;
        })->values()->all();

        $filteredCount = count($filtered);
        $kpis = $this->calculateKpis($evaluated->all());

        return view('auditoria', [
            'stores' => $filtered,
            'totalCount' => $totalCount,
            'filteredCount' => $filteredCount,
            'kpis' => $kpis,
            'filters' => $filters,
            'updatedAt' => cache()->get('dashboard_updated_at'),
        ]);
    }

    private function getStores(): ?array
    {
        $cached = cache()->get('dashboard_data');
        if ($cached) {
            return $cached;
        }
        $controller = app(DashboardController::class);
        $stores = $controller->fetchFromSheet();
        if ($stores !== null) {
            $controller->storeInCache($stores);
        }
        return $stores;
    }

    private function errorView()
    {
        $filters = ['almacen' => '', 'nivel' => '', 'estado_comite' => '', 'estado_auditoria' => '', 'filtro_500k' => ''];
        return view('auditoria', [
            'stores' => [],
            'totalCount' => 0,
            'filteredCount' => 0,
            'kpis' => ['comitesVencidos' => 0, 'auditoriaAlta' => 0, 'rotacionBaja' => 0, 'auditoriaPendiente' => 0],
            'filters' => $filters,
            'error' => 'No se pudieron obtener los datos del Google Sheet.',
            'updatedAt' => null,
        ]);
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '' || trim($value) === '0') return null;

        $formats = ['d/m/Y', 'Y-m-d', 'm/d/Y', 'Y/m/d', 'd-m-Y', 'm-d-Y'];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, trim($value));
                if ($date !== false) return $date;
            } catch (\Exception $e) {
                continue;
            }
        }

        try {
            $date = Carbon::parse(trim($value));
            if ($date->year > 2000) return $date;
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    private function evaluateAudit(array $store): array
    {
        $vigencia = $this->parseDate($store['Vigencia'] ?? '');
        $impuesto = (float) str_replace([',', '$', ' '], '', $store['Imp_Res_Audi_Mes'] ?? '0');
        $capTot = (float) str_replace([',', '$', ' '], '', $store['Cap_Tot'] ?? '0');
        $vtaMes = (float) str_replace([',', '$', ' '], '', $store['Vta_Mes'] ?? '0');
        $rotacion = $capTot > 0 ? $vtaMes / $capTot : 0;
        $fchAuditRaw = trim($store['Fch_Audit'] ?? '');
        $fchAudit = $this->parseDate($fchAuditRaw);
        $mesesSinAuditoria = $fchAudit ? $fchAudit->diffInMonths(now()) : null;

        $estadoComite = 'sin_fecha';
        if ($vigencia !== null) {
            if ($vigencia->isPast()) {
                $estadoComite = 'vencido';
            } elseif ($vigencia->diffInDays(now()) <= 30) {
                $estadoComite = 'proximo_a_vencer';
            } else {
                $estadoComite = 'vigente';
            }
        }

        $conditions = [];
        if ($vigencia !== null && $vigencia->isPast()) $conditions[] = 'comite_vencido';
        if ($impuesto > 500000) $conditions[] = 'auditoria_alta';
        if ($rotacion < 1.5) $conditions[] = 'rotacion_baja';
        $auditoriaPendiente = ($fchAudit === null) || ($mesesSinAuditoria !== null && $mesesSinAuditoria >= 3);
        if ($auditoriaPendiente) $conditions[] = 'auditoria_pendiente';

        $count = count($conditions);
        if ($count >= 2) $level = 'rojo';
        elseif ($count >= 1) $level = 'amarillo';
        else $level = 'verde';

        return compact('level', 'conditions', 'estadoComite', 'vigencia', 'impuesto', 'rotacion', 'fchAudit', 'mesesSinAuditoria');
    }

    private function calculateKpis(array $stores): array
    {
        $comitesVencidos = 0;
        $auditoriaAlta = 0;
        $rotacionBaja = 0;
        $auditoriaPendiente = 0;

        foreach ($stores as $store) {
            $a = $store['_audit'] ?? [];
            $conds = $a['conditions'] ?? [];
            if (in_array('comite_vencido', $conds)) $comitesVencidos++;
            if (in_array('auditoria_alta', $conds)) $auditoriaAlta++;
            if (in_array('rotacion_baja', $conds)) $rotacionBaja++;
            if (in_array('auditoria_pendiente', $conds)) $auditoriaPendiente++;
        }

        return compact('comitesVencidos', 'auditoriaAlta', 'rotacionBaja', 'auditoriaPendiente');
    }
}
