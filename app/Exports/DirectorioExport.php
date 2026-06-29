<?php

namespace App\Exports;

use App\Servicios\ServicioPostgresql;

class DirectorioExport extends BaseExport
{
    private const COLUMNS = [
        'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Fecha_Apertura', 'TELEFONIA', 'Señal de celular',
        'Compañía', 'INTERNET', 'CORREO', 'Direccion', 'Vta_Mes', 'VtaNeta_Mes', 'Vta_Acu', 'VtaNeta_Acu',
        'Bon_Mes', 'Cap_Tot', 'Cap_Com', 'Cap_Dic', 'Pagare_Monto', 'Pagare_Fecha', 'Fec_CRA', 'Vigencia',
        'Fch_Audit', 'Imp_Res_Audi_Mes', 'Audit_Realiza_Mes', 'Latitud', 'Longitud', 'Nom_Pre_CRA',
        'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA', 'Nom_Sec_Sup_CRA', 'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA',
        'Asam_Real_Mes',
    ];

    public function __construct(
        private ServicioPostgresql $postgres,
        private array $regionFilters,
    ) {}

    public function filename(): string
    {
        return 'directorio.xlsx';
    }

    public function headings(): array
    {
        return [
            'Nombre_Almacen' => 'Almacén',
            'No_Tienda_Actual' => 'Tienda #',
            'Municipio' => 'Municipio',
            'Fecha_Apertura' => 'Apertura',
            'TELEFONIA' => 'Tel.',
            'Señal de celular' => 'Señal',
            'Compañía' => 'Compañía',
            'INTERNET' => 'Internet',
            'CORREO' => 'Correo',
            'Direccion' => 'Dirección',
            'Vta_Mes' => 'Vta Mes',
            'VtaNeta_Mes' => 'Vta Neta',
            'Vta_Acu' => 'Vta Acum',
            'VtaNeta_Acu' => 'Vta Neta Acum',
            'Bon_Mes' => 'Bon Mes',
            'Cap_Tot' => 'Cap Total',
            'Cap_Com' => 'Cap Com',
            'Cap_Dic' => 'Cap Dic',
            'Pagare_Monto' => 'Pagaré',
            'Pagare_Fecha' => 'Pagaré Fecha',
            'Fec_CRA' => 'Fec CRA',
            'Vigencia' => 'Vigencia',
            'Nom_Pre_CRA' => 'Presidente',
            'Nom_Pre_Sup_CRA' => 'Pres. Suplente',
            'Nom_Sec_CRA' => 'Secretario',
            'Nom_Sec_Sup_CRA' => 'Sec. Suplente',
            'Nom_Tes_CRA' => 'Tesorero',
            'Nom_Vcv_CRA' => 'Vocal',
            'Nom_Voc_Gen_CRA' => 'Vocal General',
            'Asam_Real_Mes' => 'Asam. Mes',
            'Fch_Audit' => 'Fch Audit',
            'Imp_Res_Audi_Mes' => 'Impuesto',
            'Audit_Realiza_Mes' => 'Auditoría',
            'Latitud' => 'Latitud',
            'Longitud' => 'Longitud',
        ];
    }

    public function data(array $filters): iterable
    {
        return $this->postgres->exportarTiendas($this->regionFilters, $filters, self::COLUMNS, 'directorio');
    }
}
