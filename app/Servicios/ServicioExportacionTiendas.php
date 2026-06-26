<?php

namespace App\Servicios;

use App\Presenters\PresentadorTiendas;
use App\Servicios\Modulos\ServicioConsultasTiendas;

class ServicioExportacionTiendas
{
    public function __construct(
        private ServicioConsultasTiendas $consultas,
        private PresentadorTiendas $presentador,
    ) {}

    public function exportarTiendas(array $regionFilters, array $filters, array $columns, string $module, bool $usarDerivados, array $trackedDirectorioColumns): \Generator
    {
        $query = $this->consultas->conexion()->table('tiendas');
        $this->consultas->aplicarPeriodoActivo($query, $regionFilters);
        $this->consultas->aplicarFiltroRegional($query, $regionFilters);

        if ($module === 'conectividad') {
            $this->consultas->aplicarFiltrosConectividad($query, $filters);
        } elseif ($module === 'directorio') {
            $this->consultas->aplicarFiltrosDirectorio($query, $filters, $trackedDirectorioColumns);
        } elseif ($module === 'criticidad') {
            $this->consultas->aplicarFiltrosCriticidad($query, $filters, $usarDerivados);
            $columns = array_values(array_unique(array_merge($columns, ['nivel_critico', 'factores_criticos_count'])));
        } elseif ($module === 'auditoria') {
            $this->consultas->aplicarFiltrosAuditoria($query, $filters, $usarDerivados);
            $columns = array_values(array_unique(array_merge($columns, ['nivel_critico', 'estado_comite', 'rango_rotacion', 'auditoria_pendiente'])));
        } elseif ($module === 'aperturas') {
            $this->consultas->aplicarFiltrosAperturas($query, $filters);
        } elseif ($module === 'mapa') {
            $this->consultas->aplicarFiltrosMapa($query, $filters);
            $columns = array_values(array_unique(array_merge($columns, ['estado_geo'])));
        }

        $this->consultas->aplicarFiltroTiendaSalud($query, $filters['tienda_salud'] ?? '');

        foreach ($this->consultas->addTiendaSaludFlag($query->select($columns), $filters['tienda_salud'] ?? null)->orderBy('id')->cursor() as $row) {
            yield match ($module) {
                'criticidad' => $this->presentador->rowToCriticalStore($row, $columns),
                'auditoria' => $this->presentador->rowToAuditStore($row, $columns),
                'aperturas' => $this->presentador->rowToAperturaStore($row, $columns),
                'mapa' => $this->presentador->rowToGeoStore($row, $columns),
                default => $this->presentador->rowToStore($row, $columns),
            };
        }
    }
}
