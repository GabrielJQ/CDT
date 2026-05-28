<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ConnectivityController extends Controller
{
    public function index(Request $request)
    {
        $stores = $this->getStores();
        if ($stores === null) {
            return $this->errorView('No se pudieron obtener los datos del Google Sheet.');
        }

        $stores = $this->applyRegionFilter($stores);
        $totalCount = count($stores);
        $filterOptions = $this->getFilterOptions($stores);

        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'telefono' => $request->query('telefono', ''),
            'senial' => $request->query('senial', ''),
            'compania' => $request->query('compania', ''),
            'internet' => $request->query('internet', ''),
        ];

        $filtered = collect($stores)->filter(function ($store) use ($filters) {
            if ($filters['almacen'] !== '') {
                $nombre = $store['Nombre_Almacen'] ?? '';
                if (!str_contains(mb_strtoupper($nombre), mb_strtoupper($filters['almacen']))) {
                    return false;
                }
            }
            if ($filters['telefono'] === 'si' && (strtoupper(trim($store['TELEFONIA'] ?? '')) !== 'S')) return false;
            if ($filters['telefono'] === 'no' && (strtoupper(trim($store['TELEFONIA'] ?? '')) !== 'N')) return false;
            if ($filters['senial'] === 'si' && (strtoupper(trim($store['Señal de celular'] ?? '')) !== 'S')) return false;
            if ($filters['senial'] === 'no' && (strtoupper(trim($store['Señal de celular'] ?? '')) !== 'N')) return false;
            if ($filters['internet'] === 'si' && (strtoupper(trim($store['INTERNET'] ?? '')) !== 'S')) return false;
            if ($filters['internet'] === 'no' && (strtoupper(trim($store['INTERNET'] ?? '')) !== 'N')) return false;
            if ($filters['compania'] !== '') {
                $comp = strtoupper(trim($store['Compañía'] ?? ''));
                $filterComp = strtoupper(trim($filters['compania']));
                if ($filterComp === 'SIN DATO' || $filterComp === 'SIN_DATO') {
                    if ($comp !== '' && $comp !== 'SIN DATO' && $comp !== 'NINGUNO') return false;
                } elseif ($comp !== $filterComp) {
                    return false;
                }
            }
            return true;
        })->values()->all();

        $filteredCount = count($filtered);
        $kpis = $this->calculateConnectivityKpis($filtered);

        return view('connectivity', [
            'kpis' => $kpis,
            'stores' => $filtered,
            'totalCount' => $totalCount,
            'filteredCount' => $filteredCount,
            'filterOptions' => $filterOptions,
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
        $filters = ['almacen' => '', 'telefono' => '', 'senial' => '', 'compania' => '', 'internet' => ''];
        return view('connectivity', [
            'kpis' => [],
            'stores' => [],
            'totalCount' => 0,
            'filteredCount' => 0,
            'filterOptions' => ['almacenes' => [], 'companias' => []],
            'filters' => $filters,
            'error' => $message,
            'updatedAt' => null,
        ]);
    }

    private function getFilterOptions(array $stores): array
    {
        $almacenes = collect($stores)
            ->pluck('Nombre_Almacen')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $companias = collect($stores)
            ->pluck('Compañía')
            ->map(function ($v) { return trim($v) ?: 'Sin dato'; })
            ->unique()
            ->sort()
            ->values()
            ->all();

        return compact('almacenes', 'companias');
    }

    private function calculateConnectivityKpis(array $stores): array
    {
        $total = count($stores);
        $fields = [
            'TELEFONIA' => ['label' => 'Teléfono', 'icon' => '📞'],
            'INTERNET' => ['label' => 'Internet', 'icon' => '🌐'],
            'Señal de celular' => ['label' => 'Señal Celular', 'icon' => '📱'],
        ];

        $kpis = [];
        $companiaCount = [];

        foreach ($fields as $col => $info) {
            $yes = 0;
            $no = 0;
            foreach ($stores as $store) {
                $val = strtoupper(trim($store[$col] ?? ''));
                if ($val === 'S') {
                    $yes++;
                } elseif ($val === 'N') {
                    $no++;
                }
            }
            $undef = $total - $yes - $no;
            $pctYes = $total > 0 ? round($yes / $total * 100) : 0;
            $kpis[$col] = [
                'label' => $info['label'],
                'icon' => $info['icon'],
                'yes' => $yes,
                'no' => $no,
                'undef' => $undef,
                'pctYes' => $pctYes,
                'pctNo' => 100 - $pctYes,
            ];
        }

        foreach ($stores as $store) {
            $senial = strtoupper(trim($store['Señal de celular'] ?? ''));
            if ($senial !== 'S') continue;
            $comp = trim($store['Compañía'] ?? 'Sin dato');
            if ($comp === '') $comp = 'Sin dato';
            $companiaCount[$comp] = ($companiaCount[$comp] ?? 0) + 1;
        }
        arsort($companiaCount);
        $totalComp = array_sum($companiaCount);
        $companiaPct = [];
        foreach ($companiaCount as $comp => $count) {
            $companiaPct[$comp] = [
                'count' => $count,
                'pct' => $totalComp > 0 ? round($count / $totalComp * 100) : 0,
            ];
        }

        $kpis['_compania'] = $companiaPct;
        $kpis['_total'] = $total;

        return $kpis;
    }
}
