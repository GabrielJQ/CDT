<?php

namespace App\Exports;

use App\Servicios\ServicioPostgresql;

class AuditoriaExport extends BaseExport
{
    private const COLUMNS = [
        'Nombre_Almacen', 'Localidad', 'No_Tienda_Actual', 'Municipio', 'Vigencia', 'Imp_Res_Audi_Mes',
        'Cap_Dic', 'Vta_Mes', 'Fch_Audit', 'Audit_Realiza_Mes', 'Asam_Real_Mes', 'Fec_CRA',
    ];

    public function __construct(
        private ServicioPostgresql $postgres,
        private array $regionFilters,
    ) {}

    public function filename(): string
    {
        return 'auditoria.xlsx';
    }

    public function headings(): array
    {
        return [
            'Nombre_Almacen' => 'Almacén',
            'No_Tienda_Actual' => 'Tienda #',
            'Localidad' => 'Localidad',
            'Municipio' => 'Municipio',
            '_audit.vigencia' => 'Vigencia',
            '_audit.estadoComite' => 'Comité',
            'Fec_CRA' => 'Fecha CRA',
            'Asam_Real_Mes' => 'Asam. Mes',
            '_audit.fchAudit' => 'Fch. Audit',
            '_audit.mesesSinAuditoria' => 'Estado Aud.',
            '_audit.impuesto' => 'Imp. Res. Audi.',
            '_audit.rotacion' => 'Rotación',
            '_audit.level' => 'Riesgo',
        ];
    }

    public function data(array $filters): iterable
    {
        return $this->postgres->exportarTiendas($this->regionFilters, $filters, self::COLUMNS, 'auditoria');
    }
}
