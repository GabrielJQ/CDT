<?php

namespace App\Servicios;

use Illuminate\Support\Facades\Http;

class ServicioGoogleSheet
{
    public function obtenerTiendas(): ?array
    {
        $cached = cache()->get('dashboard_data');
        if ($cached) {
            return $cached;
        }

        $stores = $this->fetchDesdeSheet();
        if ($stores !== null) {
            $this->guardarEnCache($stores);
        }

        return $stores;
    }

    public function fetchDesdeSheet(): ?array
    {
        $url = config('app.google_sheet_url');
        $response = Http::withoutVerifying()->timeout(30)->get($url);

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

    public function guardarEnCache(array $stores): void
    {
        cache()->put('dashboard_data', $stores, now()->addHours(1));
        cache()->put('dashboard_updated_at', now()->toDateTimeString(), now()->addHours(1));
    }

    public function refrescar(): ?array
    {
        $stores = $this->fetchDesdeSheet();

        if ($stores !== null) {
            $this->guardarEnCache($stores);
        }

        return $stores;
    }
}
