<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $cached = cache()->get('dashboard_data');
        $updatedAt = cache()->get('dashboard_updated_at');

        if ($cached) {
            $stores = $cached;
        } else {
            $stores = $this->fetchFromSheet();
            if ($stores === null) {
                return view('dashboard', [
                    'kpis' => [],
                    'stores' => [],
                    'totalCount' => 0,
                    'filteredCount' => 0,
                    'filterOptions' => [],
                    'filters' => [],
                    'error' => 'No se pudieron obtener los datos del Google Sheet.',
                    'updatedAt' => null,
                ]);
            }
            $this->storeInCache($stores);
        }

        $totalCount = count($stores);

        // Collect filter options from full dataset
        $filterOptions = $this->getFilterOptions($stores);

        // Read filters from request
        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'telefono' => $request->query('telefono', ''),
            'senial' => $request->query('senial', ''),
            'compania' => $request->query('compania', ''),
            'internet' => $request->query('internet', ''),
        ];

        // Apply filters
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

        return view('dashboard', [
            'kpis' => $kpis,
            'stores' => $filtered,
            'totalCount' => $totalCount,
            'filteredCount' => $filteredCount,
            'filterOptions' => $filterOptions,
            'filters' => $filters,
            'updatedAt' => $updatedAt,
        ]);
    }

    public function refresh()
    {
        $stores = $this->fetchFromSheet();

        if ($stores === null) {
            return back()->with('error', 'No se pudieron refrescar los datos desde el Google Sheet.');
        }

        $this->storeInCache($stores);

        return back()->with('success', 'Datos actualizados correctamente desde el Google Sheet.');
    }

    private function fetchFromSheet(): ?array
    {
        $url = config('app.google_sheet_url');
        $response = Http::timeout(30)->get($url);

        if ($response->failed()) {
            return null;
        }

        $csv = $response->body();
        $lines = explode("\n", trim($csv));

        if (isset($lines[0]) && str_starts_with($lines[0], "\xEF\xBB\xBF")) {
            $lines[0] = substr($lines[0], 3);
        }

        if (count($lines) < 8) {
            return null;
        }

        $headerLine = $lines[6] ?? '';
        $rawHeaders = str_getcsv($headerLine);

        $stores = [];
        for ($i = 7; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '') continue;
            $row = str_getcsv($line);
            $store = [];
            foreach ($rawHeaders as $idx => $header) {
                $h = trim($header);
                if ($h === '') continue;
                $store[$h] = trim($row[$idx] ?? '');
            }
            $stores[] = $store;
        }

        return $stores;
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

    private function storeInCache(array $stores): void
    {
        cache()->put('dashboard_data', $stores, now()->addHours(1));
        cache()->put('dashboard_updated_at', now()->toDateTimeString(), now()->addHours(1));
    }
}
