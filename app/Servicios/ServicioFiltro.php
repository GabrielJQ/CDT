<?php

namespace App\Servicios;

class ServicioFiltro
{
    public function porAlmacen(array $stores, string $almacen): array
    {
        if ($almacen === '') {
            return $stores;
        }

        return collect($stores)->filter(function ($store) use ($almacen) {
            $nombre = $store['Nombre_Almacen'] ?? '';

            return str_contains(mb_strtoupper($nombre), mb_strtoupper($almacen));
        })->values()->all();
    }

    public function opcionesAlmacen(array $stores): array
    {
        return collect($stores)
            ->pluck('Nombre_Almacen')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function opcionesCompania(array $stores): array
    {
        return collect($stores)
            ->pluck('Compañía')
            ->map(function ($v) {
                return trim($v) ?: 'Sin dato';
            })
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
