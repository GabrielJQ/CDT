<?php

namespace App\Exports\CasaPorCasa;

use App\Exports\BaseExport;
use App\Servicios\ServicioCasaPorCasa;

class DirectorioExport extends BaseExport
{
    public function __construct(
        private ServicioCasaPorCasa $cxc,
        private array $uoFilter,
    ) {}

    public function filename(): string
    {
        return 'casa-x-casa-directorio.csv';
    }

    public function headings(): array
    {
        return [
            'no_tienda' => 'Tienda #',
            'almacen' => 'Almacén',
            'municipio' => 'Municipio',
            'estado' => 'Estado',
            'unidad_operativa' => 'UO',
            'encargado' => 'Encargado',
            'tipo_anaquel' => 'Tipo Anaquel',
            'anaqueles_instalados' => 'Anaqueles Instalados',
            'estatus' => 'Estatus',
            'direccion' => 'Dirección',
            'aviso_funcionamiento' => 'Aviso Funcionamiento',
            'comentarios' => 'Comentarios',
        ];
    }

    public function data(array $filters): iterable
    {
        $query = $this->cxc->directorioQuery($this->uoFilter);

        if (! empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }
        if (! empty($filters['uo'])) {
            $query->where('unidad_operativa', $filters['uo']);
        }
        if (! empty($filters['estatus'])) {
            $query->where('estatus', $filters['estatus']);
        }
        if (! empty($filters['buscar'])) {
            $term = "%{$filters['buscar']}%";
            $query->where(function ($q) use ($term) {
                $q->where('almacen', 'ILIKE', $term)
                    ->orWhere('no_tienda', 'ILIKE', $term)
                    ->orWhere('municipio', 'ILIKE', $term)
                    ->orWhere('encargado', 'ILIKE', $term);
            });
        }

        return $query->orderBy('estado')->orderBy('municipio')->cursor();
    }

    public function map(array $row): array
    {
        return [
            'no_tienda' => $row['no_tienda'] ?? '',
            'almacen' => $row['almacen'] ?? '',
            'municipio' => $row['municipio'] ?? '',
            'estado' => $row['estado'] ?? '',
            'unidad_operativa' => $row['unidad_operativa'] ?? '',
            'encargado' => $row['encargado'] ?? '',
            'tipo_anaquel' => $row['tipo_anaquel'] ?? '',
            'anaqueles_instalados' => $row['anaqueles_instalados'] ?? '',
            'estatus' => $row['estatus'] ?? '',
            'direccion' => $row['direccion'] ?? '',
            'aviso_funcionamiento' => $row['aviso_funcionamiento'] ?? '',
            'comentarios' => $row['comentarios'] ?? '',
        ];
    }
}
