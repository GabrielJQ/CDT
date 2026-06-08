<?php

namespace App\Servicios;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServicioGoogleSheet
{
    private ?string $ultimoError = null;

    public function getUltimoError(): ?string
    {
        return $this->ultimoError;
    }

    public function obtenerTiendas(): ?array
    {
        $this->ultimoError = null;

        $cached = cache()->get('dashboard_data');
        if ($cached) {
            return $cached;
        }

        try {
            $stores = $this->fetchDesdeSheet();
            $this->guardarEnCache($stores);

            return $stores;
        } catch (\RuntimeException $e) {
            $this->ultimoError = $e->getMessage();
            Log::error('[GoogleSheet] '.$e->getMessage());

            return null;
        }
    }

    public function fetchDesdeSheet(): array
    {
        $url = config('app.google_sheet_url');

        if (empty($url)) {
            throw new \RuntimeException('La URL del Google Sheet no está configurada (GOOGLE_SHEET_URL en .env)');
        }

        try {
            $response = Http::withoutVerifying()->timeout(30)->get($url);
        } catch (\Exception $e) {
            throw new \RuntimeException('No se pudo conectar con Google Sheets: '.$e->getMessage());
        }

        if ($response->failed()) {
            throw new \RuntimeException(
                'Google Sheets respondió con código '.$response->status()
                .' al descargar el archivo CSV'
            );
        }

        $csv = $response->body();
        $csvTrimmed = trim($csv);

        if (empty($csvTrimmed)) {
            throw new \RuntimeException('El archivo CSV descargado está vacío');
        }

        $lines = explode("\n", $csvTrimmed);

        if (isset($lines[0]) && str_starts_with($lines[0], "\xEF\xBB\xBF")) {
            $lines[0] = substr($lines[0], 3);
        }

        if (count($lines) < 8) {
            throw new \RuntimeException(
                'El archivo CSV tiene solo '.count($lines)
                .' líneas; se requieren al menos 8 (6 metadatos + 1 encabezados + 1 datos)'
            );
        }

        $headerLine = $lines[6] ?? '';
        $rawHeaders = str_getcsv($headerLine);

        $headerCount = count(array_filter(array_map('trim', $rawHeaders)));
        if ($headerCount < 5) {
            throw new \RuntimeException(
                'La fila de encabezados (fila 7) tiene solo '.$headerCount
                .' columnas válidas; se esperaban ~135'
            );
        }

        $stores = [];
        $omisiones = 0;
        for ($i = 7; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }

            $row = str_getcsv($line);

            if (count($row) < count($rawHeaders) * 0.5) {
                $omisiones++;

                continue;
            }

            $store = [];
            foreach ($rawHeaders as $idx => $header) {
                $h = trim($header);
                if ($h === '') {
                    continue;
                }
                $store[$h] = trim($row[$idx] ?? '');
            }
            $stores[] = $store;
        }

        if (empty($stores)) {
            throw new \RuntimeException('No se encontraron tiendas en el archivo CSV');
        }

        if ($omisiones > 0) {
            Log::warning("[GoogleSheet] {$omisiones} filas omitidas por tener menos columnas de las esperadas");
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
        $this->ultimoError = null;

        try {
            $stores = $this->fetchDesdeSheet();
            $this->guardarEnCache($stores);

            return $stores;
        } catch (\RuntimeException $e) {
            $this->ultimoError = $e->getMessage();
            Log::error('[GoogleSheet] Refrescar: '.$e->getMessage());

            return null;
        }
    }
}
