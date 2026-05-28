<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MapaController extends Controller
{
    const MEXICO_BBOX = ['latMin' => 14.5, 'latMax' => 32.7, 'lonMin' => -118.4, 'lonMax' => -86.7];

    const OAXACA_BBOX = ['latMin' => 15.6, 'latMax' => 18.7, 'lonMin' => -98.6, 'lonMax' => -94.2];

    const GEO_LABELS = [
        'OK' => ['label' => 'Válidas', 'icon' => '🟢', 'color' => 'green'],
        'SIN_COORDENADAS' => ['label' => 'Sin coordenadas', 'icon' => '⚪', 'color' => 'gray'],
        'FUERA_MEXICO' => ['label' => 'Fuera de México', 'icon' => '🔴', 'color' => 'red'],
        'FUERA_ESTADO' => ['label' => 'No corresponde a Oaxaca', 'icon' => '🟡', 'color' => 'orange'],
    ];

    public function index(Request $request)
    {
        $stores = $this->getStores();
        if ($stores === null) {
            return $this->errorView();
        }

        $totalCount = count($stores);

        $evaluated = collect($stores)->map(function ($store) {
            $geo = $this->evaluarGeo($store);
            $store['_geo'] = $geo;
            return $store;
        });

        $filters = [
            'almacen' => trim($request->query('almacen', '')),
            'estado' => $request->query('estado', ''),
            'estado_geo' => $request->query('estado_geo', ''),
        ];

        $filtered = $evaluated->filter(function ($store) use ($filters) {
            if ($filters['almacen'] !== '') {
                $nombre = $store['Nombre_Almacen'] ?? '';
                if (!str_contains(mb_strtoupper($nombre), mb_strtoupper($filters['almacen']))) {
                    return false;
                }
            }
            if ($filters['estado'] !== '') {
                $estado = strtoupper(trim($store['Estado'] ?? ''));
                if ($estado !== strtoupper(trim($filters['estado']))) {
                    return false;
                }
            }
            if ($filters['estado_geo'] !== '' && ($store['_geo']['status'] ?? '') !== $filters['estado_geo']) {
                return false;
            }
            return true;
        })->values()->all();

        $filteredCount = count($filtered);

        $stats = $this->calculateStats($evaluated->all());

        $estadosList = ['OAXACA'];

        $criticales = collect($filtered)->filter(function ($s) {
            return ($s['_geo']['status'] ?? 'OK') !== 'OK';
        })->values()->all();

        return view('mapa', [
            'stores' => $filtered,
            'criticales' => $criticales,
            'totalCount' => $totalCount,
            'filteredCount' => $filteredCount,
            'stats' => $stats,
            'estadosList' => $estadosList,
            'filters' => $filters,
            'geoLabels' => self::GEO_LABELS,
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
        return view('mapa', [
            'stores' => [],
            'criticales' => [],
            'totalCount' => 0,
            'filteredCount' => 0,
            'stats' => ['OK' => 0, 'SIN_COORDENADAS' => 0, 'FUERA_MEXICO' => 0, 'FUERA_ESTADO' => 0],
            'estadosList' => [],
            'filters' => ['almacen' => '', 'estado' => '', 'estado_geo' => ''],
            'geoLabels' => self::GEO_LABELS,
            'error' => 'No se pudieron obtener los datos del Google Sheet.',
            'updatedAt' => null,
        ]);
    }

    private function parseCoordinate(string $value): ?float
    {
        $value = trim($value);
        if ($value === '' || $value === '0') return null;

        $upper = strtoupper($value);

        $sign = 1;
        if (str_contains($upper, 'S') || str_contains($upper, 'W')) $sign = -1;

        // Remove N/S/E/W cardinal letters
        $clean = preg_replace('/[NSEWnsew]/u', ' ', $value);
        // Replace DMS symbols with space
        $clean = str_replace(['°', '\'', '"', '′', '″', '´', '¨'], ' ', $clean);
        // Replace comma with dot FIRST (Spanish decimal separator)
        $clean = str_replace(',', '.', $clean);
        // Normalize whitespace
        $clean = preg_replace('/\s+/', ' ', trim($clean));

        // Split by spaces (for DMS format)
        $parts = array_values(array_filter(explode(' ', $clean), function ($p) {
            return $p !== '' && is_numeric(str_replace('.', '', $p));
        }));

        if (count($parts) === 0) return null;

        if (count($parts) === 1) {
            $raw = $parts[0];
            $dotCount = substr_count($raw, '.');
            // Multiple dots = thousands separators (Excel format: 172.590.900.000.000)
            if ($dotCount > 1) {
                $intVal = (int) str_replace('.', '', $raw);
                $digits = strlen((string) abs($intVal));
                // Assume 2 integer digits, rest are decimal
                $scale = $digits - 2;
                return ($scale > 0) ? ($intVal / pow(10, $scale)) * $sign : (float) $raw * $sign;
            }
            return (float) $raw * $sign;
        }

        // DMS: degrees minutes (seconds optional)
        $deg = (float) $parts[0];
        $min = (float) ($parts[1] ?? 0);
        $sec = (float) ($parts[2] ?? 0);

        return ($deg + ($min / 60) + ($sec / 3600)) * $sign;
    }

    private function evaluarGeo(array $store): array
    {
        $latRaw = trim($store['Latitud'] ?? '');
        $lonRaw = trim($store['Longitud'] ?? '');
        $estado = strtoupper(trim($store['Estado'] ?? ''));

        $lat = $this->parseCoordinate($latRaw);
        $lon = $this->parseCoordinate($lonRaw);

        if ($lat === null || $lon === null) {
            return [
                'status' => 'SIN_COORDENADAS',
                'lat' => null,
                'lon' => null,
                'mensaje' => 'La tienda no tiene coordenadas registradas.',
            ];
        }

        if ($lat < self::MEXICO_BBOX['latMin'] || $lat > self::MEXICO_BBOX['latMax']
            || $lon < self::MEXICO_BBOX['lonMin'] || $lon > self::MEXICO_BBOX['lonMax']) {
            return [
                'status' => 'FUERA_MEXICO',
                'lat' => $lat,
                'lon' => $lon,
                'mensaje' => "Coordenadas ($latRaw / $lonRaw) están fuera del territorio mexicano.",
            ];
        }

        if ($estado === 'OAXACA' || $estado === 'OAXACA ' || str_contains($estado, 'OAXACA')) {
            if ($lat < self::OAXACA_BBOX['latMin'] || $lat > self::OAXACA_BBOX['latMax']
                || $lon < self::OAXACA_BBOX['lonMin'] || $lon > self::OAXACA_BBOX['lonMax']) {
                return [
                    'status' => 'FUERA_ESTADO',
                    'lat' => $lat,
                    'lon' => $lon,
                    'mensaje' => "Coordenadas ($latRaw / $lonRaw) no corresponden al estado de Oaxaca.",
                ];
            }
        }

        return [
            'status' => 'OK',
            'lat' => $lat,
            'lon' => $lon,
            'mensaje' => 'Coordenadas válidas.',
        ];
    }

    private function calculateStats(array $stores): array
    {
        $stats = ['OK' => 0, 'SIN_COORDENADAS' => 0, 'FUERA_MEXICO' => 0, 'FUERA_ESTADO' => 0];
        foreach ($stores as $store) {
            $status = $store['_geo']['status'] ?? 'OK';
            $stats[$status] = ($stats[$status] ?? 0) + 1;
        }
        return $stats;
    }
}
