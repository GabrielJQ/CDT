<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $stores = $this->getCachedStores();

        if ($stores === null) {
            return view('dashboard', [
                'totalCount' => 0,
                'connectivityKpis' => [],
                'criticalSummary' => ['rojo' => 0, 'amarillo' => 0, 'verde' => 0],
                'updatedAt' => null,
                'error' => 'No se pudieron obtener los datos del Google Sheet.',
            ]);
        }

        $totalCount = count($stores);

        $connectivityKpis = $this->calculateConnectivityKpis($stores);

        $criticalSummary = $this->calculateCriticalSummary($stores);

        return view('dashboard', [
            'totalCount' => $totalCount,
            'connectivityKpis' => $connectivityKpis,
            'criticalSummary' => $criticalSummary,
            'updatedAt' => cache()->get('dashboard_updated_at'),
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

    private function getCachedStores(): ?array
    {
        $cached = cache()->get('dashboard_data');
        if ($cached) {
            return $cached;
        }

        $stores = $this->fetchFromSheet();
        if ($stores !== null) {
            $this->storeInCache($stores);
        }

        return $stores;
    }

    public function fetchFromSheet(): ?array
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

    public function storeInCache(array $stores): void
    {
        cache()->put('dashboard_data', $stores, now()->addHours(1));
        cache()->put('dashboard_updated_at', now()->toDateTimeString(), now()->addHours(1));
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
        foreach ($fields as $col => $info) {
            $yes = 0;
            foreach ($stores as $store) {
                $val = strtoupper(trim($store[$col] ?? ''));
                if ($val === 'S') $yes++;
            }
            $kpis[$col] = [
                'label' => $info['label'],
                'icon' => $info['icon'],
                'yes' => $yes,
                'pctYes' => $total > 0 ? round($yes / $total * 100) : 0,
            ];
        }
        $kpis['_total'] = $total;

        return $kpis;
    }

    private function calculateCriticalSummary(array $stores): array
    {
        $rojo = 0;
        $amarillo = 0;
        $verde = 0;

        foreach ($stores as $store) {
            $count = 0;

            $capTot = (float) str_replace([',', '$', ' '], '', $store['Cap_Tot'] ?? '0');
            if ($capTot > 0 && $capTot < 100000) $count++;

            $vigencia = $this->parseDate($store['Vigencia'] ?? '');
            if ($vigencia !== null && $vigencia->isPast()) $count++;

            $impuesto = (float) str_replace([',', '$', ' '], '', $store['Imp_Res_Audi_Mes'] ?? '0');
            if ($impuesto > 500000) $count++;

            $pagareDate = $this->parseDate($store['Pagare_Fecha'] ?? '');
            if ($pagareDate !== null && ($pagareDate->isPast() || $pagareDate->diffInMonths(now()) <= 3)) $count++;

            $gdomarg = strtoupper(trim($store['GDOMARG'] ?? ''));
            if ($gdomarg === 'BAJA') $count++;

            $asamProg = (int) ($store['Asam_Prog_Mes'] ?? 0);
            $asamReal = (int) ($store['Asam_Real_Mes'] ?? 0);
            if ($asamProg > 0 && $asamReal === 0) $count++;

            if ($count >= 4) $rojo++;
            elseif ($count >= 2) $amarillo++;
            else $verde++;
        }

        return compact('rojo', 'amarillo', 'verde');
    }

    private function parseDate(?string $value): ?Carbon
    {
        if ($value === null || trim($value) === '' || trim($value) === '0') return null;

        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'Y/m/d', 'd-m-Y', 'm-d-Y'];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, trim($value));
                if ($date !== false) return $date;
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }
}
