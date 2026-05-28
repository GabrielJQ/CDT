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
            $headers = $cached['headers'];
            $stores = $cached['stores'];
        } else {
            $parsed = $this->fetchFromSheet();
            if ($parsed === null) {
                return view('dashboard', [
                    'headers' => [],
                    'stores' => [],
                    'numericColumns' => [],
                    'error' => 'No se pudieron obtener los datos del Google Sheet.',
                    'updatedAt' => null,
                ]);
            }
            $headers = $parsed['headers'];
            $stores = $parsed['stores'];
            $this->storeInCache(['headers' => $headers, 'stores' => $stores]);
        }

        $numericColumns = $this->detectNumericColumns($headers, $stores);

        return view('dashboard', compact(
            'headers', 'stores', 'numericColumns', 'updatedAt'
        ));
    }

    public function refresh()
    {
        $parsed = $this->fetchFromSheet();

        if ($parsed === null) {
            return back()->with('error', 'No se pudieron refrescar los datos desde el Google Sheet.');
        }

        $this->storeInCache($parsed);

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

        // Remove BOM if present
        if (isset($lines[0]) && str_starts_with($lines[0], "\xEF\xBB\xBF")) {
            $lines[0] = substr($lines[0], 3);
        }

        if (count($lines) < 8) {
            return null;
        }

        // Skip first 6 metadata rows, row index 6 is the header row
        $headerLine = $lines[6] ?? '';
        $rawHeaders = str_getcsv($headerLine);

        // Data starts at row index 7
        $allData = [];
        for ($i = 7; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '') continue;
            $row = str_getcsv($line);
            $allData[] = $row;
        }

        if (empty($allData)) {
            return null;
        }

        // Detect constant columns
        $numCols = count($rawHeaders);
        $constantCols = [];
        if ($numCols > 0) {
            for ($c = 0; $c < $numCols; $c++) {
                $firstVal = $allData[0][$c] ?? '';
                $isConstant = true;
                foreach ($allData as $row) {
                    if (($row[$c] ?? '') !== $firstVal) {
                        $isConstant = false;
                        break;
                    }
                }
                if ($isConstant) {
                    $constantCols[] = $c;
                }
            }
        }

        // Build final headers and data excluding constant columns
        $headers = [];
        $colMap = [];
        foreach ($rawHeaders as $i => $h) {
            $h = trim($h);
            if ($h === '' || in_array($i, $constantCols)) continue;
            $colMap[$i] = count($headers);
            $headers[] = $h;
        }

        $stores = [];
        foreach ($allData as $row) {
            $store = [];
            foreach ($colMap as $origIdx => $newIdx) {
                $store[$headers[$newIdx]] = trim($row[$origIdx] ?? '');
            }
            $stores[] = $store;
        }

        return compact('headers', 'stores');
    }

    private function detectNumericColumns(array $headers, array $stores): array
    {
        $numeric = [];
        if (empty($stores)) return $numeric;

        $sample = $stores[0];
        foreach ($headers as $h) {
            if (isset($sample[$h]) && is_numeric(str_replace(',', '', $sample[$h]))) {
                $sum = 0;
                foreach ($stores as $s) {
                    $val = str_replace(',', '', $s[$h] ?? '0');
                    if (is_numeric($val)) {
                        $sum += (float)$val;
                    }
                }
                $numeric[$h] = $sum;
            }
        }

        arsort($numeric);
        return array_slice($numeric, 0, 6);
    }

    private function storeInCache(array $data): void
    {
        cache()->put('dashboard_data', $data, now()->addHours(1));
        cache()->put('dashboard_updated_at', now()->toDateTimeString(), now()->addHours(1));
    }
}
