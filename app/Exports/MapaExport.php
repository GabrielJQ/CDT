<?php

namespace App\Exports;

use App\Servicios\ServicioPostgresql;

class MapaExport extends BaseExport
{
    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Estado', 'Nombre_UniOpe', 'Nombre_Regional',
        'Latitud', 'Longitud', 'Vta_Mes', 'Cap_Tot',
    ];

    public function __construct(
        private ServicioPostgresql $postgres,
        private array $regionFilters,
    ) {}

    public function filename(): string
    {
        return 'mapa.csv';
    }

    public function headings(): array
    {
        return [
            'Nombre_Almacen' => 'Almacén',
            'No_Tienda_Actual' => 'Tienda #',
            'Municipio' => 'Municipio',
            'Estado' => 'Estado',
            '_geo.lat' => 'Latitud',
            '_geo.lon' => 'Longitud',
            '_geo.status' => 'Estatus Geo',
            '_geo.mensaje' => 'Mensaje',
        ];
    }

    public function data(array $filters): iterable
    {
        return $this->postgres->exportarTiendas($this->regionFilters, $filters, self::COLUMNS, 'mapa');
    }

    public function map(array $row): array
    {
        return $row;
    }
}
