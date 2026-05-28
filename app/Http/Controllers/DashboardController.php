<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index()
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
                    'error' => 'No se pudieron obtener los datos del Google Sheet.',
                    'updatedAt' => null,
                ]);
            }
            $this->storeInCache($stores);
        }

        $kpis = $this->calculateConnectivityKpis($stores);

        return view('dashboard', compact('kpis', 'stores', 'updatedAt'));
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
            $pctYes = $total > 0 ? round($yes / $total * 100) : 0;
            $kpis[$col] = [
                'label' => $info['label'],
                'icon' => $info['icon'],
                'yes' => $yes,
                'no' => $no,
                'pctYes' => $pctYes,
                'pctNo' => 100 - $pctYes,
            ];
        }

        // Compañía distribution (only where Señal de celular = S)
        foreach ($stores as $store) {
            $senial = strtoupper(trim($store['Señal de celular'] ?? ''));
            if ($senial !== 'S') continue;
            $comp = trim($store['Compañía'] ?? 'Sin dato');
            if ($comp === '') $comp = 'Sin dato';
            $companiaCount[$comp] = ($companiaCount[$comp] ?? 0) + 1;
        }
        arsort($companiaCount);
        $totalComp = array_sum($companiaCount);
        foreach ($companiaCount as $comp => $count) {
            $companiaPct[$comp] = [
                'count' => $count,
                'pct' => $totalComp > 0 ? round($count / $totalComp * 100) : 0,
            ];
        }

        $kpis['_compania'] = $companiaPct ?? [];
        $kpis['_total'] = $total;

        return $kpis;
    }

    private function storeInCache(array $stores): void
    {
        cache()->put('dashboard_data', $stores, now()->addHours(1));
        cache()->put('dashboard_updated_at', now()->toDateTimeString(), now()->addHours(1));
    }
}
