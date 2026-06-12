<?php

namespace App\Servicios;

use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServicioPostgresql
{
    private ?string $ultimoError = null;

    public function getUltimoError(): ?string
    {
        return $this->ultimoError;
    }

    public function obtenerTiendas(array $filters = [], ?array $columns = null): array
    {
        $this->ultimoError = null;

        try {
            return $this->fetchDesdePostgres($filters, $columns);
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            Log::error('[Postgresql] '.$e->getMessage());

            return [];
        }
    }

    public function fetchDesdePostgres(array $filters = [], ?array $columns = null): array
    {
        $conn = $this->conexion();
        $count = $conn->table('tiendas')->count();
        if ($count === 0) {
            throw new \RuntimeException('La tabla tiendas está vacía en PostgreSQL');
        }

        $reverseMap = $this->reverseMap();
        $csvColumns = $columns ? array_values(array_intersect($columns, array_keys($reverseMap))) : array_keys($reverseMap);
        if ($csvColumns === []) {
            $csvColumns = array_keys($reverseMap);
        }
        $dbColumns = array_values(array_unique(array_map(fn (string $csvColumn) => $reverseMap[$csvColumn], $csvColumns)));

        $query = $conn->table('tiendas')->select($dbColumns);

        if (! empty($filters['region'])) {
            $query->where('Clave_Regional', $filters['region']);
        }
        if (! empty($filters['uo'])) {
            $query->where('Clave_UniOpe', $filters['uo']);
        }

        $stores = [];
        $query->orderBy('id')->chunk(1000, function ($rows) use (&$stores, $reverseMap, $csvColumns) {
            foreach ($rows as $row) {
                $store = [];
                foreach ($csvColumns as $csvColumn) {
                    $dbColumn = $reverseMap[$csvColumn];
                    $value = $row->{$dbColumn} ?? null;
                    $store[$csvColumn] = $this->valorAString($value);
                }
                $stores[] = $store;
            }
        });

        return $stores;
    }

    public function obtenerJerarquiaRegional(): array
    {
        try {
            $conn = $this->conexion();
            $rows = $conn->select("
                SELECT
                    \"Clave_Regional\", \"Nombre_Regional\",
                    \"Clave_UniOpe\", \"Nombre_UniOpe\",
                    COUNT(*) as total
                FROM tiendas
                WHERE \"Nombre_Regional\" IS NOT NULL AND TRIM(\"Nombre_Regional\") != ''
                GROUP BY \"Clave_Regional\", \"Nombre_Regional\", \"Clave_UniOpe\", \"Nombre_UniOpe\"
                ORDER BY \"Clave_Regional\", \"Clave_UniOpe\"
            ");

            $jerarquia = [];
            foreach ($rows as $row) {
                $claveReg = $row->{'Clave_Regional'};
                if (! isset($jerarquia[$claveReg])) {
                    $jerarquia[$claveReg] = [
                        'clave' => $claveReg,
                        'nombre' => $row->{'Nombre_Regional'},
                        'total' => 0,
                        'uos' => [],
                    ];
                }
                $jerarquia[$claveReg]['total'] += (int) $row->total;
                $jerarquia[$claveReg]['uos'][] = [
                    'clave' => $row->{'Clave_UniOpe'},
                    'nombre' => $row->{'Nombre_UniOpe'},
                    'total' => (int) $row->total,
                ];
            }

            return array_values($jerarquia);
        } catch (\Throwable $e) {
            Log::error('[Postgresql] obtenerJerarquiaRegional: '.$e->getMessage());

            return [];
        }
    }

    /**
     * @return array{rows: array<int, array<string, string>>, total: int, filtered: int, kpis: array<string, mixed>, companias: array<int, string>}
     */
    public function obtenerConectividadPaginada(array $regionFilters, array $filters, int $page, int $perPage): array
    {
        $this->ultimoError = null;

        try {
            $conn = $this->conexion();
            $base = $conn->table('tiendas');
            $this->aplicarFiltroRegional($base, $regionFilters);

            $filtered = clone $base;
            $this->aplicarFiltrosConectividad($filtered, $filters);

            $selectColumns = [
                'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET',
            ];

            $rows = (clone $filtered)
                ->select($selectColumns)
                ->orderBy('id')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get()
                ->map(fn ($row) => $this->rowToStore($row, $selectColumns))
                ->all();

            return [
                'rows' => $rows,
                'total' => (clone $base)->count(),
                'filtered' => (clone $filtered)->count(),
                'kpis' => $this->kpisConectividad(clone $filtered),
                'companias' => $this->companiasConectividad(clone $base),
            ];
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            Log::error('[Postgresql] obtenerConectividadPaginada: '.$e->getMessage());

            return ['rows' => [], 'total' => 0, 'filtered' => 0, 'kpis' => [], 'companias' => []];
        }
    }

    /**
     * @return array{rows: array<int, array<string, string>>, total: int, filtered: int, stats: array<string, mixed>}
     */
    public function obtenerDirectorioPaginado(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $trackedColumns): array
    {
        $this->ultimoError = null;

        try {
            $conn = $this->conexion();
            $base = $conn->table('tiendas');
            $this->aplicarFiltroRegional($base, $regionFilters);

            $filtered = clone $base;
            $this->aplicarFiltrosDirectorio($filtered, $filters, $trackedColumns);

            $rows = (clone $filtered)
                ->select($columns)
                ->orderBy('id')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get()
                ->map(fn ($row) => $this->rowToStore($row, $columns))
                ->all();

            return [
                'rows' => $rows,
                'total' => (clone $base)->count(),
                'filtered' => (clone $filtered)->count(),
                'stats' => $this->statsDirectorio(clone $base, $trackedColumns),
            ];
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            Log::error('[Postgresql] obtenerDirectorioPaginado: '.$e->getMessage());

            return ['rows' => [], 'total' => 0, 'filtered' => 0, 'stats' => []];
        }
    }

    public function tieneDatos(): bool
    {
        try {
            return $this->conexion()->table('tiendas')->count() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    private function aplicarFiltroRegional(Builder $query, array $filters): void
    {
        if (! empty($filters['region'])) {
            $query->where('Clave_Regional', $filters['region']);
        }
        if (! empty($filters['uo'])) {
            $query->where('Clave_UniOpe', $filters['uo']);
        }
    }

    private function aplicarFiltrosConectividad(Builder $query, array $filters): void
    {
        if (($filters['almacen'] ?? '') !== '') {
            $query->whereRaw('"Nombre_Almacen" ILIKE ?', ['%'.$filters['almacen'].'%']);
        }

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

    private function aplicarFiltrosDirectorio(Builder $query, array $filters, array $trackedColumns): void
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
            $query->whereRaw($this->camposIncompletosSql($trackedColumns));
        }

        if (! empty($filters['sinCapital'])) {
            $query->whereRaw($this->sinCapitalSql());
        }
    }

    private function kpisConectividad(Builder $query): array
    {
        $total = (clone $query)->count();
        $kpis = [];

        foreach ([
            'TELEFONIA' => ['label' => 'Teléfono', 'icon' => '📞'],
            'INTERNET' => ['label' => 'Internet', 'icon' => '🌐'],
            'Señal de celular' => ['label' => 'Señal Celular', 'icon' => '📱'],
        ] as $column => $info) {
            $yes = (clone $query)->where($column, 'S')->count();
            $no = (clone $query)->where($column, 'N')->count();
            $undef = $total - $yes - $no;
            $pctYes = $total > 0 ? round($yes / $total * 100) : 0;
            $kpis[$column] = [
                'label' => $info['label'],
                'icon' => $info['icon'],
                'yes' => $yes,
                'no' => $no,
                'undef' => $undef,
                'pctYes' => $pctYes,
                'pctNo' => 100 - $pctYes,
            ];
        }

        $companies = (clone $query)
            ->selectRaw('COALESCE(NULLIF(TRIM("Compañía"), \'\'), \'Sin dato\') as compania, COUNT(*) as total')
            ->where('Señal de celular', 'S')
            ->groupBy('compania')
            ->orderByDesc('total')
            ->get();
        $totalCompanies = (int) $companies->sum('total');
        $kpis['_compania'] = $companies->mapWithKeys(fn ($row) => [
            $row->compania => [
                'count' => (int) $row->total,
                'pct' => $totalCompanies > 0 ? round(((int) $row->total) / $totalCompanies * 100) : 0,
            ],
        ])->all();
        $kpis['_total'] = $total;

        return $kpis;
    }

    private function companiasConectividad(Builder $query): array
    {
        return (clone $query)
            ->selectRaw('DISTINCT COALESCE(NULLIF(TRIM("Compañía"), \'\'), \'Sin dato\') as compania')
            ->orderBy('compania')
            ->pluck('compania')
            ->filter()
            ->values()
            ->all();
    }

    private function statsDirectorio(Builder $query, array $trackedColumns): array
    {
        $incompletosSql = $this->camposIncompletosSql($trackedColumns);
        $sinCapitalSql = $this->sinCapitalSql();
        $comitesSql = 'NULLIF(TRIM(COALESCE("Nom_Pre_CRA"::text, \'\')), \'\') IS NULL OR NULLIF(TRIM(COALESCE("Nom_Sec_CRA"::text, \'\')), \'\') IS NULL OR NULLIF(TRIM(COALESCE("Nom_Tes_CRA"::text, \'\')), \'\') IS NULL';

        $row = (clone $query)->selectRaw("\n            SUM(CASE WHEN {$incompletosSql} THEN 1 ELSE 0 END) as incompletos,\n            SUM(CASE WHEN {$sinCapitalSql} THEN 1 ELSE 0 END) as sin_capital,\n            SUM(CASE WHEN {$comitesSql} THEN 1 ELSE 0 END) as comites_incompletos,\n            SUM(CASE WHEN COALESCE(\"Asam_Real_Mes\", 0) > 0 THEN 1 ELSE 0 END) as asambleas_mes,\n            SUM(CASE WHEN COALESCE(\"Cap_Dic\", 0) - COALESCE(\"Cap_Tot\", 0) > 0 THEN 1 ELSE 0 END) as tiendas_faltante,\n            SUM(GREATEST(COALESCE(\"Cap_Dic\", 0) - COALESCE(\"Cap_Tot\", 0), 0)) as importe_faltante,\n            SUM(CASE WHEN \"Pagare_Fecha\" IS NOT NULL AND \"Pagare_Fecha\" <= CURRENT_DATE - INTERVAL '1 year' THEN 1 ELSE 0 END) as pagares_vencidos,\n            SUM(CASE WHEN \"Pagare_Fecha\" IS NOT NULL AND \"Pagare_Fecha\" <= CURRENT_DATE - INTERVAL '1 year' THEN COALESCE(\"Pagare_Monto\", 0) ELSE 0 END) as importe_pagares_vencidos\n        ")->first();

        return [
            'incompletos' => (int) ($row->incompletos ?? 0),
            'sinCapital' => (int) ($row->sin_capital ?? 0),
            'comitesIncompletos' => (int) ($row->comites_incompletos ?? 0),
            'asambleasMes' => (int) ($row->asambleas_mes ?? 0),
            'tiendasFaltante' => (int) ($row->tiendas_faltante ?? 0),
            'importeFaltante' => (float) ($row->importe_faltante ?? 0),
            'pagaresVencidos' => (int) ($row->pagares_vencidos ?? 0),
            'importePagaresVencidos' => (float) ($row->importe_pagares_vencidos ?? 0),
        ];
    }

    private function camposIncompletosSql(array $columns): string
    {
        return collect($columns)
            ->reject(fn (string $column) => str_contains($column, 'Sup_CRA'))
            ->map(fn (string $column) => 'NULLIF(TRIM(COALESCE("'.$column.'"::text, \'\')), \'\') IS NULL OR TRIM(COALESCE("'.$column.'"::text, \'\')) = \'0\'')
            ->implode(' OR ');
    }

    private function sinCapitalSql(): string
    {
        return '"Cap_Tot" IS NULL OR COALESCE("Cap_Tot", 0) = 0';
    }

    private function rowToStore(object $row, array $columns): array
    {
        $store = [];
        foreach ($columns as $column) {
            $store[$column] = $this->valorAString($row->{$column} ?? null);
        }

        return $store;
    }

    private function conexion(): Connection
    {
        return DB::connection('pgsql_imports');
    }

    private function reverseMap(): array
    {
        $map = [];
        foreach (config('importacion.column_mapping', []) as $dbCol => $csvCol) {
            $map[$csvCol] = $dbCol;
        }

        return $map;
    }

    private function valorAString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if ($value instanceof Carbon) {
            return $value->format('d/m/Y');
        }

        if (is_float($value) || is_int($value)) {
            if ($value == (int) $value) {
                return number_format((int) $value, 0, '.', '');
            }

            return rtrim(rtrim(number_format($value, 7, '.', ''), '0'), '.');
        }

        return (string) $value;
    }
}
