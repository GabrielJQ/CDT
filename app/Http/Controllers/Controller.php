<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function applyRegionFilter(array $stores): array
    {
        $uo = session('region_filter', '');
        if ($uo === '' || $uo === null) return $stores;

        return collect($stores)->filter(function ($s) use ($uo) {
            return ($s['Nombre_UniOpe'] ?? '') === $uo;
        })->values()->all();
    }

    protected function getRegionOptions(): array
    {
        return [
            'U.O. OAXACA' => 'Oaxaca',
            'U.O. ISTMO' => 'Istmo',
            'U.O. MIXTECA' => 'Mixteca',
        ];
    }
}
