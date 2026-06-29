<?php

namespace App\Exports;

use App\Servicios\ServicioPostgresql;

class ConectividadExport extends BaseExport
{
    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET',
    ];

    public function __construct(
        private ServicioPostgresql $postgres,
        private array $regionFilters,
    ) {}

    public function filename(): string
    {
        return 'conectividad.xlsx';
    }

    public function headings(): array
    {
        return [
            'Nombre_Almacen' => 'Almacén',
            'No_Tienda_Actual' => 'Tienda #',
            'Municipio' => 'Municipio',
            'TELEFONIA' => 'Teléfono fijo',
            'Señal de celular' => 'Señal Celular',
            'Compañía' => 'Compañía',
            'INTERNET' => 'Internet',
        ];
    }

    public function data(array $filters): iterable
    {
        return $this->postgres->exportarTiendas($this->regionFilters, $filters, self::COLUMNS, 'conectividad');
    }
}
