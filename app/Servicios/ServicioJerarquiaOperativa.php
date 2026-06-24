<?php

namespace App\Servicios;

use App\Models\Region;
use App\Models\UnidadOperativa;
use Illuminate\Support\Facades\DB;

class ServicioJerarquiaOperativa
{
    /**
     * @return array{regiones: int, unidades: int}
     */
    public function sincronizar(): array
    {
        $rows = DB::connection('pgsql_imports')
            ->table('tiendas')
            ->select(['Clave_Regional', 'Nombre_Regional', 'Clave_UniOpe', 'Nombre_UniOpe'])
            ->where('es_activo', true)
            ->whereNotNull('Clave_Regional')
            ->whereNotNull('Clave_UniOpe')
            ->distinct()
            ->orderBy('Clave_Regional')
            ->orderBy('Clave_UniOpe')
            ->get();

        $regiones = 0;
        $unidades = 0;

        foreach ($rows as $row) {
            $regionClave = trim((string) $row->{'Clave_Regional'});
            $regionNombre = trim((string) $row->{'Nombre_Regional'});
            $uoClave = trim((string) $row->{'Clave_UniOpe'});
            $uoNombre = trim((string) $row->{'Nombre_UniOpe'});

            if ($regionClave === '' || $uoClave === '') {
                continue;
            }

            $region = Region::query()->updateOrCreate([
                'clave' => $regionClave,
            ], [
                'nombre' => $regionNombre !== '' ? $regionNombre : $regionClave,
                'is_active' => true,
            ]);
            $regiones++;

            UnidadOperativa::query()->updateOrCreate([
                'region_id' => $region->id,
                'clave' => $uoClave,
            ], [
                'nombre' => $uoNombre !== '' ? $uoNombre : $uoClave,
                'is_active' => true,
            ]);
            $unidades++;
        }

        return ['regiones' => $regiones, 'unidades' => $unidades];
    }
}
