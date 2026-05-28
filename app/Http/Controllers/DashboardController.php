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
                $stores = [];
            } else {
                $this->storeInCache($stores);
            }
        }

        if (empty($stores)) {
            return view('dashboard', [
                'stores' => [],
                'totalLosses' => 0,
                'storesWithShortage' => 0,
                'totalStores' => 0,
                'error' => 'No hay datos en caché ni se pudo obtener del Google Sheet.',
            ]);
        }

        $totalLosses = collect($stores)->sum('Perdidas_Monetarias');
        $storesWithShortage = collect($stores)->where('Faltas_Personal', '>', 0)->count();
        $totalStores = count($stores);

        return view('dashboard', compact(
            'stores', 'totalLosses', 'storesWithShortage', 'totalStores', 'updatedAt'
        ));
    }

    public function refresh()
    {
        $stores = $this->fetchFromSheet();

        if ($stores === null) {
            return back()->with('error', 'No se pudo refrescar los datos desde el Google Sheet.');
        }

        $this->storeInCache($stores);

        return back()->with('success', 'Datos actualizados correctamente desde el Google Sheet.');
    }

    private function fetchFromSheet(): ?array
    {
        $url = config('app.google_sheet_url');
        $response = Http::timeout(15)->get($url);

        if ($response->failed()) {
            return null;
        }

        $csv = $response->body();
        $lines = explode("\n", trim($csv));

        if (count($lines) < 2) {
            return null;
        }

        $headers = str_getcsv(array_shift($lines));
        $stores = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            $row = str_getcsv($line);
            $store = [];
            foreach ($headers as $i => $header) {
                $store[trim($header)] = trim($row[$i] ?? '');
            }
            $stores[] = $store;
        }

        return $stores;
    }

    private function storeInCache(array $stores): void
    {
        cache()->put('dashboard_data', $stores, now()->addHours(1));
        cache()->put('dashboard_updated_at', now()->toDateTimeString(), now()->addHours(1));
    }
}
