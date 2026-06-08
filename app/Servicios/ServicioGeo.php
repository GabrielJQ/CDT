<?php

namespace App\Servicios;

class ServicioGeo
{
    const MEXICO_BBOX = ['latMin' => 14.5, 'latMax' => 32.7, 'lonMin' => -118.4, 'lonMax' => -86.7];

    const OAXACA_BBOX = ['latMin' => 15.3, 'latMax' => 18.8, 'lonMin' => -98.8, 'lonMax' => -93.7];

    const GEO_LABELS = [
        'OK' => ['label' => 'Válidas', 'icon' => '🟢', 'color' => 'green'],
        'SIN_COORDENADAS' => ['label' => 'Sin coordenadas', 'icon' => '⚪', 'color' => 'gray'],
        'FUERA_MEXICO' => ['label' => 'Fuera de México', 'icon' => '🔴', 'color' => 'red'],
        'FUERA_ESTADO' => ['label' => 'No corresponde a Oaxaca', 'icon' => '🟡', 'color' => 'orange'],
    ];

    public function parsearCoordenada(string $value): ?float
    {
        $value = trim($value);
        if ($value === '' || $value === '0') {
            return null;
        }

        $upper = strtoupper($value);

        $sign = 1;
        if (str_contains($upper, 'S') || str_contains($upper, 'W')) {
            $sign = -1;
        }

        $clean = preg_replace('/[NSEWnsew]/u', ' ', $value);
        $clean = str_replace(['°', '\'', '"', '′', '″', '´', '¨'], ' ', $clean);
        $clean = str_replace(',', '.', $clean);
        $clean = preg_replace('/\s+/', ' ', trim($clean));

        $parts = array_values(array_filter(explode(' ', $clean), function ($p) {
            return $p !== '' && is_numeric(str_replace('.', '', $p));
        }));

        if (count($parts) === 0) {
            return null;
        }

        if (count($parts) === 1) {
            $raw = $parts[0];
            $dotCount = substr_count($raw, '.');
            if ($dotCount > 1) {
                $intVal = (int) str_replace('.', '', $raw);
                $digits = strlen((string) abs($intVal));
                $scale = $digits - 2;

                return ($scale > 0) ? ($intVal / pow(10, $scale)) * $sign : (float) $raw * $sign;
            }

            return (float) $raw * $sign;
        }

        $deg = (float) $parts[0];
        $min = (float) ($parts[1] ?? 0);
        $sec = (float) ($parts[2] ?? 0);

        return ($deg + ($min / 60) + ($sec / 3600)) * $sign;
    }

    public function evaluarGeo(array $store): array
    {
        $latRaw = trim($store['Latitud'] ?? '');
        $lonRaw = trim($store['Longitud'] ?? '');
        $estado = strtoupper(trim($store['Estado'] ?? ''));

        $lat = $this->parsearCoordenada($latRaw);
        $lon = $this->parsearCoordenada($lonRaw);

        if ($lat === null || $lon === null || ($lat === 0.0 && $lon === 0.0)) {
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

    public function calcularStats(array $stores): array
    {
        $stats = ['OK' => 0, 'SIN_COORDENADAS' => 0, 'FUERA_MEXICO' => 0, 'FUERA_ESTADO' => 0];
        foreach ($stores as $store) {
            $status = $store['_geo']['status'] ?? 'OK';
            $stats[$status] = ($stats[$status] ?? 0) + 1;
        }

        return $stats;
    }
}
