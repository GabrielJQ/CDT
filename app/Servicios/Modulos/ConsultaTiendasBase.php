<?php

namespace App\Servicios\Modulos;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

abstract class ConsultaTiendasBase
{
    public function conexion(): Connection
    {
        return DB::connection(config('database.imports'));
    }

    protected function queryBase(array $regionFilters): Builder
    {
        $query = $this->conexion()->table('tiendas');
        $this->aplicarPeriodoActivo($query, $regionFilters);

        return $query;
    }

    public function aplicarPeriodoActivo(Builder $query, array $filters = []): void
    {
        if (! empty($filters['periodo_importacion_id'])) {
            $query->where('periodo_importacion_id', $filters['periodo_importacion_id']);
        } else {
            $query->where('es_activo', true);
        }
    }

    public function aplicarFiltroRegional(Builder $query, array $filters): void
    {
        if (! empty($filters['region'])) {
            $query->where('Clave_Regional', $filters['region']);
        }
        if (! empty($filters['uo'])) {
            $query->where('Clave_UniOpe', $filters['uo']);
        }
    }

    protected function aplicarFiltroTiendaSalud(Builder $query, string $filter): void
    {
        if ($filter === 'si') {
            $query->where('es_tienda_salud', true);
        } elseif ($filter === 'no') {
            $query->where(function ($q) {
                $q->whereNull('es_tienda_salud')->orWhere('es_tienda_salud', false);
            });
        }
    }

    protected function aplicarAlmacenSearch(Builder $query, ?string $almacen): void
    {
        if ($almacen !== null && $almacen !== '') {
            $query->whereRaw('"Nombre_Almacen" ILIKE ?', ['%'.$almacen.'%']);
        }
    }

    protected function paginate(Builder $query, int $page, int $perPage): LengthAwarePaginator
    {
        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    protected function totalCount(Builder $query): int
    {
        return (clone $query)->count();
    }

    protected function filteredCount(Builder $query): int
    {
        return (clone $query)->count();
    }
}
