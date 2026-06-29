<?php

namespace App\Exports;

use App\Servicios\ServicioPostgresql;

class AperturasExport extends BaseExport
{
    private const COLUMNS = [
        'Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio', 'Fecha_Apertura',
    ];

    public function __construct(
        private ServicioPostgresql $postgres,
        private array $regionFilters,
    ) {}

    public function filename(): string
    {
        return 'aperturas.csv';
    }

    public function headings(): array
    {
        return [
            'Nombre_Almacen' => 'Almacén',
            'No_Tienda_Actual' => 'Tienda #',
            'Localidad' => 'Localidad',
            'Municipio' => 'Municipio',
            'Fecha_Apertura' => 'Fecha Apertura',
            '_fecha_apertura' => 'Apertura (parseada)',
            '_antiguedad' => 'Antigüedad',
        ];
    }

    public function data(array $filters): iterable
    {
        return $this->postgres->exportarTiendas($this->regionFilters, $filters, self::COLUMNS, 'aperturas');
    }

    public function map(array $row): array
    {
        return $row;
    }
}
