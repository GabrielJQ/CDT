<?php

namespace App\Exports\CasaPorCasa;

use App\Exports\BaseExport;
use App\Servicios\ServicioCasaPorCasa;

class MapaExport extends BaseExport
{
    public function __construct(
        private ServicioCasaPorCasa $cxc,
        private array $uoFilter,
    ) {}

    public function filename(): string
    {
        return 'casa-x-casa-mapa.xlsx';
    }

    public function headings(): array
    {
        return [
            'almacen' => 'Almacén',
            'no_tienda' => 'Tienda #',
            'municipio' => 'Municipio',
            'estado' => 'Estado',
            'unidad_operativa' => 'UO',
            'tipo_anaquel' => 'Tipo Anaquel',
            'anaqueles_instalados' => 'Anaqueles Instalados',
            'latitud' => 'Latitud',
            'longitud' => 'Longitud',
        ];
    }

    public function data(array $filters): iterable
    {
        $query = $this->cxc->mapaQuery($this->uoFilter)
            ->select([
                'id', 'almacen', 'no_tienda', 'municipio', 'estado', 'unidad_operativa',
                'tipo_anaquel', 'anaqueles_instalados', 'latitud', 'longitud',
            ]);

        if (! empty($filters['almacen'])) {
            $query->where('almacen', 'ILIKE', "%{$filters['almacen']}%");
        }
        if (! empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }
        if (! empty($filters['uo'])) {
            $query->where('unidad_operativa', $filters['uo']);
        }
        if (! empty($filters['estatus'])) {
            $query->where('estatus', $filters['estatus']);
        }
        if (! empty($filters['anaquelStatus'])) {
            if ($filters['anaquelStatus'] === 'instalados') {
                $query->where('anaqueles_instalados', true);
            } elseif ($filters['anaquelStatus'] === 'pendientes') {
                $query->where('anaqueles_instalados', false);
            }
        }
        if (! empty($filters['buscar'])) {
            $term = "%{$filters['buscar']}%";
            $query->where(function ($q) use ($term) {
                $q->where('almacen', 'ILIKE', $term)
                    ->orWhere('no_tienda', 'ILIKE', $term)
                    ->orWhere('municipio', 'ILIKE', $term);
            });
        }

        return $query->orderBy('id')->cursor()->map(fn (object $row) => (array) $row);
    }

    public function map(array $row): array
    {
        return [
            'almacen' => $row['almacen'] ?? '',
            'no_tienda' => $row['no_tienda'] ?? '',
            'municipio' => $row['municipio'] ?? '',
            'estado' => $row['estado'] ?? '',
            'unidad_operativa' => $row['unidad_operativa'] ?? '',
            'tipo_anaquel' => $row['tipo_anaquel'] ?? '',
            'anaqueles_instalados' => ($row['anaqueles_instalados'] ?? false) ? 'INSTALADO' : 'PENDIENTE',
            'latitud' => $row['latitud'] ?? '',
            'longitud' => $row['longitud'] ?? '',
        ];
    }
}
