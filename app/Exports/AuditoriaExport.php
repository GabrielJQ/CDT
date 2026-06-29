<?php

namespace App\Exports;

use App\Servicios\ServicioPostgresql;

class AuditoriaExport extends BaseExport
{
    private const COLUMNS = [
        'Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio', 'Vigencia', 'Imp_Res_Audi_Mes',
        'Cap_Dic', 'Vta_Mes', 'Fch_Audit', 'Audit_Realiza_Mes', 'Asam_Real_Mes',
    ];

    public function __construct(
        private ServicioPostgresql $postgres,
        private array $regionFilters,
    ) {}

    public function filename(): string
    {
        return 'auditoria.csv';
    }

    public function headings(): array
    {
        return [
            'Nombre_Almacen' => 'Almacén',
            'No_Tienda_Actual' => 'Tienda #',
            'Localidad' => 'Localidad',
            'Municipio' => 'Municipio',
            'Vigencia' => 'Vigencia',
            '_audit.estadoComite' => 'Estado Comité',
            '_audit.fchAudit' => 'Fch Auditoría',
            '_audit.mesesSinAuditoria' => 'Meses Sin Auditoría',
            '_audit.impuesto' => 'Impuesto',
            '_audit.rotacion' => 'Rotación',
            '_audit.level' => 'Nivel Riesgo',
        ];
    }

    public function data(array $filters): iterable
    {
        return $this->postgres->exportarTiendas($this->regionFilters, $filters, self::COLUMNS, 'auditoria');
    }

    public function map(array $row): array
    {
        return $row;
    }
}
