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
        return 'casa-x-casa-mapa.csv';
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

        return $query->orderBy('id')->cursor();
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
            'anaqueles_instalados' => $row['anaqueles_instalados'] ?? '',
            'latitud' => $row['latitud'] ?? '',
            'longitud' => $row['longitud'] ?? '',
        ];
    }
}
