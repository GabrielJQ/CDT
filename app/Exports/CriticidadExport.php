<?php

namespace App\Exports;

use App\Presenters\IndicadorPresenter;
use App\Servicios\ServicioPostgresql;

class CriticidadExport extends BaseExport
{
    private const COLUMNS = [
        'Estado', 'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Cap_Tot', 'Cap_Dic', 'Vigencia',
        'Imp_Res_Audi_Mes', 'Pagare_Fecha', 'Vta_Mes', 'Asam_Prog_Mes', 'Asam_Real_Mes',
    ];

    public function __construct(
        private ServicioPostgresql $postgres,
        private array $regionFilters,
    ) {}

    public function filename(): string
    {
        return 'informacion-tiendas.xlsx';
    }

    public function headings(): array
    {
        return [
            '_critico.level' => 'Estado',
            'Nombre_Almacen' => 'Almacén',
            'No_Tienda_Actual' => 'Tienda #',
            'Municipio' => 'Municipio',
            '_critico.count' => 'Factores',
            '_detalle_factores' => 'Detalle',
        ];
    }

    public function map(array $row): array
    {
        if (isset($row['_critico']['level'])) {
            $row['_critico']['level'] = IndicadorPresenter::levelDisplayLabel($row['_critico']['level']);
        }

        return $row;
    }

    public function data(array $filters): iterable
    {
        foreach ($this->postgres->exportarTiendas($this->regionFilters, $filters, self::COLUMNS, 'criticidad') as $store) {
            $critico = $store['_critico'] ?? [];
            $detalle = [];
            foreach (($critico['conditions'] ?? []) as $key => $active) {
                if ($active) {
                    $label = $critico['labels'][$key]['label'] ?? $key;
                    $detail = $critico['labels'][$key]['detail'] ?? '';
                    $detalle[] = $detail ? "$label ($detail)" : $label;
                }
            }
            $store['_detalle_factores'] = implode('; ', $detalle);
            yield $store;
        }
    }
}
