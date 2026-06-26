<?php

namespace App\Servicios\Modulos;

use App\Servicios\ServicioIndicadorCriticidad;
use Illuminate\Database\Query\Builder;

class ServicioConsultasTiendas extends ConsultaTiendasBase
{
    public function __construct(
        private ServicioIndicadorCriticidad $indicadores,
    ) {}

    public function aplicarFiltrosConectividad(Builder $query, array $filters): void
    {
        $this->aplicarAlmacenSearch($query, $filters['almacen'] ?? null);

        foreach ([
            'telefono' => 'TELEFONIA',
            'senial' => 'Señal de celular',
            'internet' => 'INTERNET',
        ] as $filterKey => $column) {
            if (($filters[$filterKey] ?? '') === 'si') {
                $query->where($column, 'S');
            }
            if (($filters[$filterKey] ?? '') === 'no') {
                $query->where($column, 'N');
            }
        }

        if (($filters['compania'] ?? '') !== '') {
            $company = strtoupper(trim($filters['compania']));
            if ($company === 'SIN DATO' || $company === 'SIN_DATO') {
                $query->where(function ($query) {
                    $query->whereNull('Compañía')
                        ->orWhere('Compañía', '')
                        ->orWhereRaw('UPPER(TRIM("Compañía")) IN (?, ?)', ['SIN DATO', 'NINGUNO']);
                });
            } else {
                $query->whereRaw('UPPER(TRIM("Compañía")) = ?', [$company]);
            }
        }
    }

    public function aplicarFiltrosDirectorio(Builder $query, array $filters, array $trackedColumns): void
    {
        if (($filters['q'] ?? '') !== '') {
            $term = '%'.$filters['q'].'%';
            $query->where(function ($query) use ($term) {
                $query->whereRaw('"Nombre_Almacen" ILIKE ?', [$term])
                    ->orWhereRaw('"No_Tienda_Actual"::text ILIKE ?', [$term])
                    ->orWhereRaw('"Municipio" ILIKE ?', [$term]);
            });
        }

        if (! empty($filters['incompletos'])) {
            $query->whereRaw($this->indicadores->camposIncompletosSql($trackedColumns));
        }

        if (! empty($filters['sinCapital'])) {
            $query->whereRaw($this->indicadores->sinCapitalSql());
        }
    }

    public function aplicarFiltrosCriticidad(Builder $query, array $filters, bool $usarDerivados = false): void
    {
        $this->aplicarAlmacenSearch($query, $filters['almacen'] ?? null);

        if (($filters['nivel'] ?? '') !== '') {
            if ($usarDerivados) {
                $query->where('nivel_critico', $filters['nivel']);
            } else {
                $query->whereRaw($this->indicadores->nivelCriticoSql().' = ?', [$filters['nivel']]);
            }
        }

        if (($filters['indicador'] ?? '') !== '') {
            $condition = $this->indicadores->indicadorCriticoSql($filters['indicador'], $usarDerivados);
            if ($condition !== null) {
                $query->whereRaw($condition);
            }
        }
    }

    public function aplicarFiltrosAuditoria(Builder $query, array $filters, bool $usarDerivados = false): void
    {
        $this->aplicarAlmacenSearch($query, $filters['almacen'] ?? null);

        if (($filters['nivel'] ?? '') !== '') {
            if ($usarDerivados) {
                $query->where('nivel_critico', $filters['nivel']);
            } else {
                $query->whereRaw($this->indicadores->nivelCriticoSql().' = ?', [$filters['nivel']]);
            }
        }

        if (($filters['estado_comite'] ?? '') !== '') {
            if ($usarDerivados) {
                $query->where('estado_comite', $filters['estado_comite']);
            } else {
                $query->whereRaw($this->indicadores->estadoComiteSql().' = ?', [$filters['estado_comite']]);
            }
        }

        if (($filters['estado_auditoria'] ?? '') === 'vencida') {
            if ($usarDerivados) {
                $query->where('auditoria_pendiente', true);
            } else {
                $query->whereRaw($this->indicadores->auditoriaPendienteSql());
            }
        } elseif (($filters['estado_auditoria'] ?? '') === 'al_dia') {
            if ($usarDerivados) {
                $query->where('auditoria_pendiente', false)->whereNotNull('Fch_Audit');
            } else {
                $query->whereRaw('NOT ('.$this->indicadores->auditoriaPendienteSql().')')->whereNotNull('Fch_Audit');
            }
        } elseif (($filters['estado_auditoria'] ?? '') === 'sin_fecha') {
            $query->whereNull('Fch_Audit');
        }

        if (($filters['filtro_500k'] ?? '') === 'si') {
            $query->where('Imp_Res_Audi_Mes', '>', 500000);
        } elseif (($filters['filtro_500k'] ?? '') === 'no') {
            $query->where(function ($query) {
                $query->whereNull('Imp_Res_Audi_Mes')->orWhere('Imp_Res_Audi_Mes', '<=', 500000);
            });
        }

        if (($filters['rango_rotacion'] ?? '') !== '') {
            if ($usarDerivados) {
                $query->where('rango_rotacion', $filters['rango_rotacion']);
            } else {
                $query->whereRaw($this->indicadores->rangoRotacionSql().' = ?', [$filters['rango_rotacion']]);
            }
        }

        if (($filters['tiempo_auditoria'] ?? '') === 'mes') {
            $query->where('Audit_Realiza_Mes', '>', 0);
        } elseif (($filters['tiempo_auditoria'] ?? '') === 'trimestre') {
            if ($usarDerivados) {
                $query->where('auditoria_pendiente', true);
            } else {
                $query->whereRaw($this->indicadores->auditoriaPendienteSql());
            }
        } elseif (($filters['tiempo_auditoria'] ?? '') === 'anio') {
            $query->where(function ($query) {
                $query->whereNull('Fch_Audit')
                    ->orWhere('Fch_Audit', '<=', now()->subYear()->toDateString());
            });
        }

        if (($filters['asambleas_mes'] ?? '') === 'si') {
            $query->where('Asam_Real_Mes', '>', 0);
        } elseif (($filters['asambleas_mes'] ?? '') === 'no') {
            $query->where(function ($query) {
                $query->whereNull('Asam_Real_Mes')->orWhere('Asam_Real_Mes', '<=', 0);
            });
        }
    }

    public function aplicarFiltrosAperturas(Builder $query, array $filters): void
    {
        $this->aplicarAlmacenSearch($query, $filters['almacen'] ?? null);

        if (($filters['desde'] ?? '') !== '') {
            $query->where('Fecha_Apertura', '>=', $filters['desde']);
        }

        if (($filters['hasta'] ?? '') !== '') {
            $query->where('Fecha_Apertura', '<=', $filters['hasta']);
        }
    }

    public function aplicarFiltrosMapa(Builder $query, array $filters): void
    {
        $this->aplicarAlmacenSearch($query, $filters['almacen'] ?? null);

        if (($filters['estado_geo'] ?? '') !== '') {
            if (($filters['estado_geo'] ?? '') === 'INCIDENCIAS') {
                $query->whereIn('estado_geo', ['SIN_COORDENADAS', 'FUERA_MEXICO']);

                return;
            }

            $query->where('estado_geo', $filters['estado_geo']);
        }
    }

    public function aplicarOrdenTabla(Builder $query, array $sort, array $allowedColumns, array $expressionColumns = []): void
    {
        $column = $sort['column'] ?? null;
        $direction = ($sort['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        if ($column !== null && isset($expressionColumns[$column])) {
            $query->orderByRaw($expressionColumns[$column].' '.$direction.' NULLS LAST')->orderBy('id');

            return;
        }

        if ($column !== null && in_array($column, $allowedColumns, true)) {
            $query->orderBy($column, $direction)->orderBy('id');

            return;
        }

        $query->orderBy('id');
    }

    public function aplicarOrdenCriticidad(Builder $query, array $sort, array $columns, bool $usarDerivados): void
    {
        $countSql = $usarDerivados ? 'factores_criticos_count' : $this->indicadores->factoresCriticosCountSql();
        $levelSql = $usarDerivados
            ? "CASE nivel_critico WHEN 'verde' THEN 1 WHEN 'amarillo' THEN 2 WHEN 'rojo' THEN 3 ELSE 0 END"
            : "CASE ({$this->indicadores->nivelCriticoSql()}) WHEN 'verde' THEN 1 WHEN 'amarillo' THEN 2 WHEN 'rojo' THEN 3 ELSE 0 END";

        $expressions = [
            'Estado' => $levelSql,
            'Factores' => $countSql,
            'Detalle' => $countSql,
        ];

        $column = $sort['column'] ?? null;
        if ($column !== null) {
            $this->aplicarOrdenTabla($query, $sort, $columns, $expressions);

            return;
        }

        $query->orderByRaw($countSql.' DESC NULLS LAST')->orderBy('id');
    }

    public function aplicarOrdenAuditoria(Builder $query, array $sort, array $columns, bool $usarDerivados): void
    {
        $auditoriaPendienteSql = $usarDerivados ? 'CASE WHEN auditoria_pendiente THEN 1 ELSE 0 END' : 'CASE WHEN '.$this->indicadores->auditoriaPendienteSql().' THEN 1 ELSE 0 END';
        $estadoComiteSql = $usarDerivados
            ? "CASE estado_comite WHEN 'vigente' THEN 1 WHEN 'proximo_a_vencer' THEN 2 WHEN 'vencido' THEN 3 ELSE 0 END"
            : "CASE ({$this->indicadores->estadoComiteSql()}) WHEN 'vigente' THEN 1 WHEN 'proximo_a_vencer' THEN 2 WHEN 'vencido' THEN 3 ELSE 0 END";
        $rangoRotacionSql = $usarDerivados
            ? "CASE rango_rotacion WHEN 'cero' THEN 0 WHEN 'critico' THEN 1 WHEN 'amarillo' THEN 2 WHEN 'optimo' THEN 3 ELSE 0 END"
            : "CASE ({$this->indicadores->rangoRotacionSql()}) WHEN 'cero' THEN 0 WHEN 'critico' THEN 1 WHEN 'amarillo' THEN 2 WHEN 'optimo' THEN 3 ELSE 0 END";
        $rotacionSql = 'CASE WHEN COALESCE("Cap_Dic", 0) > 0 THEN COALESCE("Vta_Mes", 0) / NULLIF("Cap_Dic", 0) ELSE 0 END';
        $nivelSql = $usarDerivados
            ? "CASE nivel_critico WHEN 'verde' THEN 1 WHEN 'amarillo' THEN 2 WHEN 'rojo' THEN 3 ELSE 0 END"
            : "CASE ({$this->indicadores->nivelCriticoSql()}) WHEN 'verde' THEN 1 WHEN 'amarillo' THEN 2 WHEN 'rojo' THEN 3 ELSE 0 END";

        $expressions = [
            'Comite' => $estadoComiteSql,
            'Estado_Aud' => $auditoriaPendienteSql,
            'Rotacion' => $rotacionSql,
            'Riesgo' => $nivelSql,
            'rango_rotacion' => $rangoRotacionSql,
        ];

        $column = $sort['column'] ?? null;
        if ($column !== null) {
            $this->aplicarOrdenTabla($query, $sort, $columns, $expressions);

            return;
        }

        $query->orderByRaw($auditoriaPendienteSql.' DESC NULLS LAST')->orderBy('id');
    }

    public function aplicarOrdenAperturas(Builder $query, array $sort, array $columns): void
    {
        $column = $sort['column'] ?? null;
        $direction = ($sort['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        if ($column === '_fecha_apertura') {
            $query->orderBy('Fecha_Apertura', $direction)->orderBy('id');

            return;
        }

        if ($column === '_antiguedad') {
            $query->orderBy('Fecha_Apertura', $direction === 'asc' ? 'desc' : 'asc')->orderBy('id');

            return;
        }

        if ($column !== null) {
            $this->aplicarOrdenTabla($query, $sort, $columns);

            return;
        }

        $query->orderByDesc('Fecha_Apertura')->orderBy('id');
    }

    public function aplicarFiltroTiendaSalud(Builder $query, string $filter): void
    {
        if ($filter === '') {
            return;
        }

        if ($filter === 'salud') {
            $query->whereRaw(
                '("No_Tienda_Actual"::text, "Nombre_Almacen", "Estado", "Municipio") IN ('
                .'SELECT cxc.no_tienda::text, cxc.almacen, cxc.estado, cxc.municipio '
                .'FROM tiendas_casa_x_casa cxc WHERE cxc.es_activo = true)'
            );
        } elseif ($filter === 'regular') {
            $query->whereRaw(
                'NOT EXISTS (SELECT 1 FROM tiendas_casa_x_casa cxc WHERE cxc.es_activo = true '
                .'AND cxc.no_tienda::text = tiendas."No_Tienda_Actual"::text '
                .'AND cxc.almacen = tiendas."Nombre_Almacen" '
                .'AND cxc.estado = tiendas."Estado" '
                .'AND cxc.municipio = tiendas."Municipio")'
            );
        }
    }

    public function aplicarBounds(Builder $query, array $bounds, string $latColumn, string $lonColumn): void
    {
        foreach (['north', 'south', 'east', 'west'] as $key) {
            if (! isset($bounds[$key]) || ! is_numeric($bounds[$key])) {
                return;
            }
        }

        $north = min(90, (float) $bounds['north']);
        $south = max(-90, (float) $bounds['south']);
        $east = min(180, (float) $bounds['east']);
        $west = max(-180, (float) $bounds['west']);

        $latTextExpression = 'NULLIF(TRIM("'.$latColumn.'"::text), \'\')';
        $lonTextExpression = 'NULLIF(TRIM("'.$lonColumn.'"::text), \'\')';
        $latExpression = $latTextExpression.'::double precision';
        $lonExpression = $lonTextExpression.'::double precision';

        $query->whereRaw($latTextExpression.' ~ ?', ['^-?\d+(\.\d+)?$']);
        $query->whereRaw($lonTextExpression.' ~ ?', ['^-?\d+(\.\d+)?$']);

        $query->whereRaw($latExpression.' BETWEEN ? AND ?', [min($south, $north), max($south, $north)]);
        if ($west <= $east) {
            $query->whereRaw($lonExpression.' BETWEEN ? AND ?', [$west, $east]);
        } else {
            $query->where(function ($query) use ($lonExpression, $west, $east) {
                $query->whereRaw($lonExpression.' BETWEEN ? AND ?', [$west, 180])
                    ->orWhereRaw($lonExpression.' BETWEEN ? AND ?', [-180, $east]);
            });
        }
    }

    public function selectMapaColumns(Builder $query, array $columns, ?string $tiendaSaludFilter = null): Builder
    {
        return $this->addTiendaSaludFlag(
            $query->select(array_values(array_unique(array_merge($columns, ['estado_geo'])))),
            $tiendaSaludFilter,
        );
    }

    public function addTiendaSaludFlag(Builder $query, ?string $tiendaSaludFilter = null): Builder
    {
        if ($tiendaSaludFilter === 'salud') {
            return $query->selectRaw('true as es_tienda_salud_bienestar');
        }

        if ($tiendaSaludFilter === 'regular') {
            return $query->selectRaw('false as es_tienda_salud_bienestar');
        }

        return $query->selectRaw('EXISTS (
            SELECT 1
            FROM tiendas_casa_x_casa cxc
            WHERE cxc.no_tienda::text = tiendas."No_Tienda_Actual"::text
              AND cxc.almacen = tiendas."Nombre_Almacen"
              AND cxc.estado = tiendas."Estado"
              AND cxc.municipio = tiendas."Municipio"
              AND cxc.es_activo = true
            LIMIT 1
        ) as es_tienda_salud_bienestar');
    }

    public function reverseMap(): array
    {
        $map = [];
        foreach (config('importacion.column_mapping', []) as $dbCol => $csvCol) {
            $map[$csvCol] = $dbCol;
        }

        return $map;
    }
}
