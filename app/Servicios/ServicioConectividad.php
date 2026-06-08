<?php

namespace App\Servicios;

class ServicioConectividad
{
    private array $fields = [
        'TELEFONIA' => ['label' => 'Teléfono', 'icon' => '📞'],
        'INTERNET' => ['label' => 'Internet', 'icon' => '🌐'],
        'Señal de celular' => ['label' => 'Señal Celular', 'icon' => '📱'],
    ];

    public function calcularKpis(array $stores): array
    {
        $total = count($stores);

        $kpis = [];
        foreach ($this->fields as $col => $info) {
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

        $companiaCount = [];
        foreach ($stores as $store) {
            $senial = strtoupper(trim($store['Señal de celular'] ?? ''));
            if ($senial !== 'S') {
                continue;
            }
            $comp = trim($store['Compañía'] ?? 'Sin dato');
            if ($comp === '') {
                $comp = 'Sin dato';
            }
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

    public function resumenSimple(array $stores): array
    {
        $total = count($stores);

        $kpis = [];
        foreach ($this->fields as $col => $info) {
            $yes = 0;
            foreach ($stores as $store) {
                $val = strtoupper(trim($store[$col] ?? ''));
                if ($val === 'S') {
                    $yes++;
                }
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

    public function contarSinConectividad(array $stores): int
    {
        $count = 0;
        foreach ($stores as $store) {
            $tel = strtoupper(trim($store['TELEFONIA'] ?? ''));
            $int = strtoupper(trim($store['INTERNET'] ?? ''));
            $cel = strtoupper(trim($store['Señal de celular'] ?? ''));
            if ($tel !== 'S' && $int !== 'S' && $cel !== 'S') {
                $count++;
            }
        }

        return $count;
    }
}
