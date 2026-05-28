<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;

class CriticalStoresController extends Controller
{
    public function index(Request $request)
    {
        $stores = $this->getStores();
        if ($stores === null) {
            return $this->errorView('No se pudieron obtener los datos del Google Sheet.');
        }

        $stores = $this->applyRegionFilter($stores);
        $totalCount = count($stores);

        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'nivel' => $request->query('nivel', ''),
        ];

        $evaluated = collect($stores)->map(function ($store) {
            $evaluation = $this->evaluateStore($store);
            return array_merge($store, ['_critico' => $evaluation]);
        });

        $filtered = $evaluated->filter(function ($store) use ($filters) {
            if ($filters['almacen'] !== '') {
                $nombre = $store['Nombre_Almacen'] ?? '';
                if (!str_contains(mb_strtoupper($nombre), mb_strtoupper($filters['almacen']))) {
                    return false;
                }
            }
            if ($filters['nivel'] !== '' && $store['_critico']['level'] !== $filters['nivel']) {
                return false;
            }
            return true;
        })->values()->all();

        $filteredCount = count($filtered);

        $summary = $this->calculateSummary($evaluated->all());

        return view('critical-stores', [
            'stores' => $filtered,
            'totalCount' => $totalCount,
            'filteredCount' => $filteredCount,
            'summary' => $summary,
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

    private function errorView(string $message)
    {
        $filters = ['almacen' => '', 'nivel' => ''];
        return view('critical-stores', [
            'stores' => [],
            'totalCount' => 0,
            'filteredCount' => 0,
            'summary' => ['rojo' => 0, 'amarillo' => 0, 'verde' => 0, 'desglose' => []],
            'filters' => $filters,
            'error' => $message,
            'updatedAt' => null,
        ]);
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '' || trim($value) === '0') return null;

        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'Y/m/d', 'd-m-Y', 'm-d-Y'];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, trim($value));
                if ($date !== false) {
                    return $date;
                }
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

    private function evaluateStore(array $store): array
    {
        $conditions = [];
        $labels = [];

        $capTot = (float) str_replace([',', '$', ' '], '', $store['Cap_Tot'] ?? '0');
        $conditions['capital_bajo'] = $capTot > 0 && $capTot < 100000;
        $labels['capital_bajo'] = [
            'label' => 'Capital bajo',
            'detail' => 'Cap_Tot: $' . number_format($capTot, 2),
            'icon' => '💰',
        ];

        $vigencia = $store['Vigencia'] ?? '';
        $vigenciaDate = $this->parseDate($vigencia);
        $conditions['comite_vencido'] = $vigenciaDate !== null && $vigenciaDate->isPast();
        $labels['comite_vencido'] = [
            'label' => 'Comité vencido',
            'detail' => 'Vigencia: ' . ($vigenciaDate ? $vigenciaDate->format('d/m/Y') : 'Sin fecha'),
            'icon' => '📅',
        ];

        $impuestoRaw = str_replace([',', '$', ' '], '', $store['Imp_Res_Audi_Mes'] ?? '0');
        $impuesto = (float) $impuestoRaw;
        $conditions['auditoria_elevada'] = $impuesto > 500000;
        $labels['auditoria_elevada'] = [
            'label' => 'Auditoría > $500k',
            'detail' => 'Imp_Res_Audi_Mes: $' . number_format($impuesto, 2),
            'icon' => '🔍',
        ];

        $pagareFecha = $store['Pagare_Fecha'] ?? '';
        $pagareDate = $this->parseDate($pagareFecha);
        $conditions['pagare_proximo'] = false;
        if ($pagareDate !== null) {
            $conditions['pagare_proximo'] = $pagareDate->isPast() || $pagareDate->diffInMonths(now()) <= 3;
        }
        $labels['pagare_proximo'] = [
            'label' => 'Pagare próximo',
            'detail' => 'Pagare_Fecha: ' . ($pagareDate ? $pagareDate->format('d/m/Y') : 'Sin fecha'),
            'icon' => '📄',
        ];

        $vtaMes = (float) str_replace([',', '$', ' '], '', $store['Vta_Mes'] ?? '0');
        $rotacion = $capTot > 0 ? $vtaMes / $capTot : 0;
        $conditions['rotacion_baja'] = $rotacion < 1.5;
        $labels['rotacion_baja'] = [
            'label' => 'Rotación baja',
            'detail' => 'Vta_Mes/Cap_Tot: ' . number_format($rotacion, 2),
            'icon' => '📉',
        ];

        $asamProg = (int) ($store['Asam_Prog_Mes'] ?? 0);
        $asamReal = (int) ($store['Asam_Real_Mes'] ?? 0);
        $conditions['asamblea_pendiente'] = $asamProg > 0 && $asamReal === 0;
        $labels['asamblea_pendiente'] = [
            'label' => 'Asamblea pendiente',
            'detail' => 'Programadas: ' . $asamProg . ', Realizadas: ' . $asamReal,
            'icon' => '🗳️',
        ];

        $activeCount = count(array_filter($conditions));

        if ($activeCount >= 4) $level = 'rojo';
        elseif ($activeCount >= 2) $level = 'amarillo';
        else $level = 'verde';

        return [
            'conditions' => $conditions,
            'labels' => $labels,
            'count' => $activeCount,
            'level' => $level,
        ];
    }

    private function calculateSummary(array $stores): array
    {
        $rojo = 0;
        $amarillo = 0;
        $verde = 0;
        $desglose = [];

        foreach ($stores as $store) {
            $level = $store['_critico']['level'];
            $$level++;

            foreach ($store['_critico']['conditions'] as $key => $active) {
                if ($active) {
                    $desglose[$key] = ($desglose[$key] ?? 0) + 1;
                }
            }
        }

        arsort($desglose);

        $condLabels = [
            'capital_bajo' => '💰 Capital bajo',
            'comite_vencido' => '📅 Comité vencido',
            'auditoria_elevada' => '🔍 Auditoría > $500k',
            'pagare_proximo' => '📄 Pagare próximo',
            'rotacion_baja' => '📉 Rotación baja',
            'asamblea_pendiente' => '🗳️ Asamblea pendiente',
        ];

        $desgloseLabels = [];
        foreach ($desglose as $key => $count) {
            $desgloseLabels[] = [
                'key' => $key,
                'label' => $condLabels[$key] ?? $key,
                'count' => $count,
            ];
        }

        return compact('rojo', 'amarillo', 'verde', 'desgloseLabels');
    }
}
