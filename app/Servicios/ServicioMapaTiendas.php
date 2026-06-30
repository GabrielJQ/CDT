<?php

namespace App\Servicios;

use App\Presenters\PresentadorTiendas;
use App\Servicios\Modulos\ServicioConsultasTiendas;

class ServicioMapaTiendas
{
    public function __construct(
        private ServicioConsultasTiendas $consultas,
        private PresentadorTiendas $presentador,
    ) {}

    public function obtenerMapa(array $regionFilters, array $filters, array $columns): array
    {
        $query = $this->consultas->conexion()->table('tiendas');
        $this->consultas->aplicarPeriodoActivo($query, $regionFilters);
        $this->consultas->aplicarFiltroRegional($query, $regionFilters);
        $this->consultas->aplicarFiltrosMapa($query, $filters);
        $this->consultas->aplicarFiltroTiendaSalud($query, $filters['tienda_salud'] ?? '');

        return $this->consultas->selectMapaColumns($query, $columns, $filters['tienda_salud'] ?? null)
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => $this->presentador->rowToGeoStore($row, $columns))
            ->all();
    }

    public function obtenerMapaViewport(array $regionFilters, array $filters, array $bounds, array $columns, int $limit = 3000): array
    {
        $query = $this->consultas->conexion()->table('tiendas');
        $this->consultas->aplicarPeriodoActivo($query, $regionFilters);
        $this->consultas->aplicarFiltroRegional($query, $regionFilters);
        $this->consultas->aplicarFiltrosMapa($query, $filters);
        $this->consultas->aplicarFiltroTiendaSalud($query, $filters['tienda_salud'] ?? '');
        if (! in_array($filters['estado_geo'] ?? '', ['FUERA_MEXICO', 'INCIDENCIAS'], true)) {
            $this->consultas->aplicarBounds($query, $bounds, 'Latitud', 'Longitud');
        }

        return $this->consultas->selectMapaColumns($query, $columns, $filters['tienda_salud'] ?? null)
            ->whereNotNull('Latitud')
            ->whereNotNull('Longitud')
            ->where('Latitud', '!=', '0')
            ->where('Longitud', '!=', '0')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->presentador->rowToGeoStore($row, $columns))
            ->all();
    }

    public function contarMapaFiltrado(array $regionFilters, array $filters): int
    {
        $query = $this->consultas->conexion()->table('tiendas');
        $this->consultas->aplicarPeriodoActivo($query, $regionFilters);
        $this->consultas->aplicarFiltroRegional($query, $regionFilters);
        $this->consultas->aplicarFiltrosMapa($query, $filters);
        $this->consultas->aplicarFiltroTiendaSalud($query, $filters['tienda_salud'] ?? '');

        return $query->count();
    }

    public function obtenerIncidenciasMapaPaginadas(array $regionFilters, array $filters, array $columns, ?string $sort = null, string $direction = 'asc', int $page = 1, int $perPage = 50): array
    {
        $filters['sort'] = $sort;
        $filters['direction'] = $direction;

        $countQuery = $this->consultas->conexion()->table('tiendas');
        $this->consultas->aplicarPeriodoActivo($countQuery, $regionFilters);
        $this->consultas->aplicarFiltroRegional($countQuery, $regionFilters);
        $this->consultas->aplicarFiltrosMapa($countQuery, $filters);
        $this->consultas->aplicarFiltroTiendaSalud($countQuery, $filters['tienda_salud'] ?? '');
        $countQuery->whereNotIn('estado_geo', ['OK']);
        $total = $countQuery->count();

        $dataQuery = $this->consultas->conexion()->table('tiendas');
        $this->consultas->aplicarPeriodoActivo($dataQuery, $regionFilters);
        $this->consultas->aplicarFiltroRegional($dataQuery, $regionFilters);
        $this->consultas->aplicarFiltrosMapa($dataQuery, $filters);
        $this->consultas->aplicarFiltroTiendaSalud($dataQuery, $filters['tienda_salud'] ?? '');
        $dataQuery->whereNotIn('estado_geo', ['OK']);

        $sortable = ['Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Estado'];
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        if ($sort && in_array($sort, $sortable, true)) {
            $dataQuery->orderBy($sort, $direction);
        } else {
            $dataQuery->orderBy('id');
        }

        $offset = max(0, ($page - 1) * $perPage);
        $rows = $this->consultas->selectMapaColumns($dataQuery, $columns, $filters['tienda_salud'] ?? null)
            ->limit($perPage)
            ->offset($offset)
            ->get()
            ->map(fn ($row) => $this->presentador->rowToGeoStore($row, $columns))
            ->all();

        return ['items' => $rows, 'total' => $total];
    }
}
