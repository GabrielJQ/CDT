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

    /**
     * @var array<string, bool>
     */
    private array $derivadosCompletosCache = [];

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
        $countQuery = $conn->table('tiendas');
        $this->aplicarPeriodoActivo($countQuery);
        $count = $countQuery->count();
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
        $this->aplicarPeriodoActivo($query);

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
                    COUNT(*) as total,
                    COUNT(DISTINCT \"Nombre_Almacen\") as almacenes
                FROM tiendas
                WHERE es_activo = true AND \"Nombre_Regional\" IS NOT NULL AND TRIM(\"Nombre_Regional\") != ''
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
                        'almacenes' => 0,
                        'uos' => [],
                    ];
                }
                $jerarquia[$claveReg]['total'] += (int) $row->total;
                $jerarquia[$claveReg]['almacenes'] += (int) $row->almacenes;
                $jerarquia[$claveReg]['uos'][] = [
                    'clave' => $row->{'Clave_UniOpe'},
                    'nombre' => $row->{'Nombre_UniOpe'},
                    'total' => (int) $row->total,
                    'almacenes' => (int) $row->almacenes,
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
    public function obtenerConectividadPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $sort = []): array
    {
        $this->ultimoError = null;

        try {
            $conn = $this->conexion();
            $base = $conn->table('tiendas');
            $this->aplicarPeriodoActivo($base);
            $this->aplicarFiltroRegional($base, $regionFilters);

            $filtered = clone $base;
            $this->aplicarFiltrosConectividad($filtered, $filters);

            $selectColumns = [
                'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET',
            ];

            $rowsQuery = $this->addTiendaSaludFlag((clone $filtered)->select($selectColumns));
            $this->aplicarOrdenTabla($rowsQuery, $sort, $selectColumns);

            $rows = $rowsQuery->offset(($page - 1) * $perPage)
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
    public function obtenerDirectorioPaginado(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $trackedColumns, array $sort = []): array
    {
        $this->ultimoError = null;

        try {
            $conn = $this->conexion();
            $base = $conn->table('tiendas');
            $this->aplicarPeriodoActivo($base);
            $this->aplicarFiltroRegional($base, $regionFilters);

            $filtered = clone $base;
            $this->aplicarFiltrosDirectorio($filtered, $filters, $trackedColumns);

            $rowsQuery = $this->addTiendaSaludFlag((clone $filtered)->select($columns));
            $this->aplicarOrdenTabla($rowsQuery, $sort, $columns);

            $rows = $rowsQuery->offset(($page - 1) * $perPage)
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

    /**
     * @return array{rows: array<int, array<string, mixed>>, total: int, filtered: int, summary: array<string, mixed>}
     */
    public function obtenerCriticidadPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $sort = []): array
    {
        $this->ultimoError = null;

        try {
            $conn = $this->conexion();
            $base = $conn->table('tiendas');
            $this->aplicarPeriodoActivo($base);
            $this->aplicarFiltroRegional($base, $regionFilters);
            $usarDerivados = $this->derivadosCompletos($regionFilters);

            $filtered = clone $base;
            $this->aplicarFiltrosCriticidad($filtered, $filters, $usarDerivados);

            $selectColumns = array_values(array_unique(array_merge($columns, ['nivel_critico', 'factores_criticos_count'])));
            $rowsQuery = $this->addTiendaSaludFlag((clone $filtered)->select($selectColumns));
            $this->aplicarOrdenCriticidad($rowsQuery, $sort, $columns, $usarDerivados);

            $rows = $rowsQuery->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get()
                ->map(fn ($row) => $this->rowToCriticalStore($row, $columns))
                ->all();

            return [
                'rows' => $rows,
                'total' => (clone $base)->count(),
                'filtered' => (clone $filtered)->count(),
                'summary' => $this->resumenCriticidad(clone $base, $usarDerivados),
            ];
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            Log::error('[Postgresql] obtenerCriticidadPaginada: '.$e->getMessage());

            return ['rows' => [], 'total' => 0, 'filtered' => 0, 'summary' => []];
        }
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, total: int, filtered: int, kpis: array<string, mixed>}
     */
    public function obtenerAuditoriaPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $sort = []): array
    {
        $this->ultimoError = null;

        try {
            $conn = $this->conexion();
            $base = $conn->table('tiendas');
            $this->aplicarPeriodoActivo($base);
            $this->aplicarFiltroRegional($base, $regionFilters);
            $usarDerivados = $this->derivadosCompletos($regionFilters);

            $filtered = clone $base;
            $this->aplicarFiltrosAuditoria($filtered, $filters, $usarDerivados);

            $selectColumns = array_values(array_unique(array_merge($columns, ['nivel_critico', 'estado_comite', 'rango_rotacion', 'auditoria_pendiente'])));
            $rowsQuery = $this->addTiendaSaludFlag((clone $filtered)->select($selectColumns));
            $this->aplicarOrdenAuditoria($rowsQuery, $sort, $columns, $usarDerivados);

            $rows = $rowsQuery->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get()
                ->map(fn ($row) => $this->rowToAuditStore($row, $columns))
                ->all();

            return [
                'rows' => $rows,
                'total' => (clone $base)->count(),
                'filtered' => (clone $filtered)->count(),
                'kpis' => $this->kpisAuditoria(clone $base, $usarDerivados),
            ];
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            Log::error('[Postgresql] obtenerAuditoriaPaginada: '.$e->getMessage());

            return ['rows' => [], 'total' => 0, 'filtered' => 0, 'kpis' => []];
        }
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, total: int, filtered: int, kpis: array<string, int>}
     */
    public function obtenerAperturasPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $sort = []): array
    {
        $this->ultimoError = null;

        try {
            $conn = $this->conexion();
            $base = $conn->table('tiendas');
            $this->aplicarPeriodoActivo($base);
            $this->aplicarFiltroRegional($base, $regionFilters);

            $filtered = clone $base;
            $this->aplicarFiltrosAperturas($filtered, $filters);

            $rowsQuery = $this->addTiendaSaludFlag((clone $filtered)->select($columns));
            $this->aplicarOrdenAperturas($rowsQuery, $sort, $columns);

            $rows = $rowsQuery->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get()
                ->map(fn ($row) => $this->rowToAperturaStore($row, $columns))
                ->all();

            return [
                'rows' => $rows,
                'total' => (clone $base)->count(),
                'filtered' => (clone $filtered)->count(),
                'kpis' => $this->kpisAperturas(clone $filtered),
            ];
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            Log::error('[Postgresql] obtenerAperturasPaginada: '.$e->getMessage());

            return ['rows' => [], 'total' => 0, 'filtered' => 0, 'kpis' => []];
        }
    }

    public function obtenerMapa(array $regionFilters, array $filters, array $columns): array
    {
        $query = $this->conexion()->table('tiendas');
        $this->aplicarPeriodoActivo($query);
        $this->aplicarFiltroRegional($query, $regionFilters);
        $this->aplicarFiltrosMapa($query, array_diff_key($filters, ['estado_geo' => true]));

        $rows = $this->selectMapaColumns($query, $columns)
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => $this->rowToGeoStore($row, $columns))
            ->all();

        return $this->filtrarGeoCalculado($rows, $filters['estado_geo'] ?? '');
    }

    public function obtenerMapaViewport(array $regionFilters, array $filters, array $bounds, array $columns, int $limit = 3000): array
    {
        $query = $this->conexion()->table('tiendas');
        $this->aplicarPeriodoActivo($query);
        $this->aplicarFiltroRegional($query, $regionFilters);
        $this->aplicarFiltrosMapa($query, array_diff_key($filters, ['estado_geo' => true]));
        if (! in_array($filters['estado_geo'] ?? '', ['FUERA_MEXICO', 'INCIDENCIAS'], true)) {
            $this->aplicarBounds($query, $bounds, 'Latitud', 'Longitud');
        }

        $rows = $this->selectMapaColumns($query, $columns)
            ->whereNotNull('Latitud')
            ->whereNotNull('Longitud')
            ->where('Latitud', '!=', '0')
            ->where('Longitud', '!=', '0')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->rowToGeoStore($row, $columns))
            ->all();

        return $this->filtrarGeoCalculado($rows, $filters['estado_geo'] ?? '');
    }

    public function obtenerDashboardMetricas(array $regionFilters): array
    {
        $base = $this->conexion()->table('tiendas');
        $this->aplicarPeriodoActivo($base);
        $this->aplicarFiltroRegional($base, $regionFilters);

        $total = (clone $base)->count();
        $usarDerivados = $this->derivadosCompletos($regionFilters);
        $aperturasPorMes = $this->aperturasPorMes(clone $base);
        $directorioStats = $this->statsDirectorio(clone $base, $this->trackedDirectorioColumns());
        $completos = max(0, $total - ($directorioStats['incompletos'] ?? 0));

        return [
            'totalCount' => $total,
            'connectivityKpis' => $this->kpisConectividad(clone $base),
            'criticalSummary' => $this->resumenCriticidad(clone $base, $usarDerivados),
            'sinConectividad' => $this->sinConectividadCount(clone $base),
            'aperturasEsteMes' => $this->aperturasEsteMesCount(clone $base),
            'geoStats' => $this->geoStats(clone $base, $usarDerivados),
            'aperturasKpi' => $this->aperturasKpiDashboard(clone $base),
            'aperturasPorMes' => $aperturasPorMes,
            'directorioStats' => ['completos' => $completos, 'incompletos' => (int) ($directorioStats['incompletos'] ?? 0)],
            'auditoriaKpis' => $this->kpisAuditoria(clone $base, $usarDerivados),
        ];
    }

    public function exportarTiendas(array $regionFilters, array $filters, array $columns, string $module): \Generator
    {
        $query = $this->conexion()->table('tiendas');
        $this->aplicarPeriodoActivo($query);
        $this->aplicarFiltroRegional($query, $regionFilters);

        if ($module === 'conectividad') {
            $this->aplicarFiltrosConectividad($query, $filters);
        } elseif ($module === 'directorio') {
            $this->aplicarFiltrosDirectorio($query, $filters, $this->trackedDirectorioColumns());
        } elseif ($module === 'criticidad') {
            $this->aplicarFiltrosCriticidad($query, $filters, $this->derivadosCompletos($regionFilters));
            $columns = array_values(array_unique(array_merge($columns, ['nivel_critico', 'factores_criticos_count'])));
        } elseif ($module === 'auditoria') {
            $this->aplicarFiltrosAuditoria($query, $filters, $this->derivadosCompletos($regionFilters));
            $columns = array_values(array_unique(array_merge($columns, ['nivel_critico', 'estado_comite', 'rango_rotacion', 'auditoria_pendiente'])));
        } elseif ($module === 'aperturas') {
            $this->aplicarFiltrosAperturas($query, $filters);
        } elseif ($module === 'mapa') {
            $this->aplicarFiltrosMapa($query, $filters);
            $columns = array_values(array_unique(array_merge($columns, ['estado_geo'])));
        }

        foreach ($this->addTiendaSaludFlag($query->select($columns))->orderBy('id')->cursor() as $row) {
            yield match ($module) {
                'criticidad' => $this->rowToCriticalStore($row, $columns),
                'auditoria' => $this->rowToAuditStore($row, $columns),
                'aperturas' => $this->rowToAperturaStore($row, $columns),
                'mapa' => $this->rowToGeoStore($row, $columns),
                default => $this->rowToStore($row, $columns),
            };
        }
    }

    public function tieneDatos(): bool
    {
        try {
            $query = $this->conexion()->table('tiendas');
            $this->aplicarPeriodoActivo($query);

            return $query->count() > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public function derivadosCompletos(array $regionFilters = []): bool
    {
        $cacheKey = json_encode($regionFilters);
        if (isset($this->derivadosCompletosCache[$cacheKey])) {
            return $this->derivadosCompletosCache[$cacheKey];
        }

        $diagnostico = $this->diagnosticoDerivados($regionFilters);
        $completos = ($diagnostico['total'] ?? 0) > 0
            && ($diagnostico['nivel_critico_nulos'] ?? 1) === 0
            && ($diagnostico['factores_criticos_count_nulos'] ?? 1) === 0
            && ($diagnostico['estado_comite_nulos'] ?? 1) === 0
            && ($diagnostico['rango_rotacion_nulos'] ?? 1) === 0
            && ($diagnostico['auditoria_pendiente_nulos'] ?? 1) === 0;

        return $this->derivadosCompletosCache[$cacheKey] = $completos;
    }

    /**
     * @return array<string, int>
     */
    public function diagnosticoDerivados(array $regionFilters = []): array
    {
        try {
            $query = $this->conexion()->table('tiendas');
            $this->aplicarPeriodoActivo($query);
            $this->aplicarFiltroRegional($query, $regionFilters);
            $row = $query->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN nivel_critico IS NULL THEN 1 ELSE 0 END) as nivel_critico_nulos,
                SUM(CASE WHEN factores_criticos_count IS NULL THEN 1 ELSE 0 END) as factores_criticos_count_nulos,
                SUM(CASE WHEN estado_geo IS NULL THEN 1 ELSE 0 END) as estado_geo_nulos,
                SUM(CASE WHEN estado_comite IS NULL THEN 1 ELSE 0 END) as estado_comite_nulos,
                SUM(CASE WHEN rango_rotacion IS NULL THEN 1 ELSE 0 END) as rango_rotacion_nulos,
                SUM(CASE WHEN auditoria_pendiente IS NULL THEN 1 ELSE 0 END) as auditoria_pendiente_nulos
            ')->first();

            return [
                'total' => (int) ($row->total ?? 0),
                'nivel_critico_nulos' => (int) ($row->nivel_critico_nulos ?? 0),
                'factores_criticos_count_nulos' => (int) ($row->factores_criticos_count_nulos ?? 0),
                'estado_geo_nulos' => (int) ($row->estado_geo_nulos ?? 0),
                'estado_comite_nulos' => (int) ($row->estado_comite_nulos ?? 0),
                'rango_rotacion_nulos' => (int) ($row->rango_rotacion_nulos ?? 0),
                'auditoria_pendiente_nulos' => (int) ($row->auditoria_pendiente_nulos ?? 0),
            ];
        } catch (\Throwable $e) {
            Log::error('[Postgresql] diagnosticoDerivados: '.$e->getMessage());

            return [
                'total' => 0,
                'nivel_critico_nulos' => 1,
                'factores_criticos_count_nulos' => 1,
                'estado_geo_nulos' => 1,
                'estado_comite_nulos' => 1,
                'rango_rotacion_nulos' => 1,
                'auditoria_pendiente_nulos' => 1,
            ];
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

    private function aplicarPeriodoActivo(Builder $query): void
    {
        $query->where('es_activo', true);
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

    private function aplicarFiltrosCriticidad(Builder $query, array $filters, bool $usarDerivados = false): void
    {
        if (($filters['almacen'] ?? '') !== '') {
            $query->whereRaw('"Nombre_Almacen" ILIKE ?', ['%'.$filters['almacen'].'%']);
        }

        if (($filters['nivel'] ?? '') !== '') {
            if ($usarDerivados) {
                $query->where('nivel_critico', $filters['nivel']);
            } else {
                $query->whereRaw($this->nivelCriticoSql().' = ?', [$filters['nivel']]);
            }
        }

        if (($filters['indicador'] ?? '') !== '') {
            $condition = $this->indicadorCriticoSql($filters['indicador'], $usarDerivados);
            if ($condition !== null) {
                $query->whereRaw($condition);
            }
        }
    }

    private function aplicarFiltrosAuditoria(Builder $query, array $filters, bool $usarDerivados = false): void
    {
        if (($filters['almacen'] ?? '') !== '') {
            $query->whereRaw('"Nombre_Almacen" ILIKE ?', ['%'.$filters['almacen'].'%']);
        }

        if (($filters['nivel'] ?? '') !== '') {
            if ($usarDerivados) {
                $query->where('nivel_critico', $filters['nivel']);
            } else {
                $query->whereRaw($this->nivelCriticoSql().' = ?', [$filters['nivel']]);
            }
        }

        if (($filters['estado_comite'] ?? '') !== '') {
            if ($usarDerivados) {
                $query->where('estado_comite', $filters['estado_comite']);
            } else {
                $query->whereRaw($this->estadoComiteSql().' = ?', [$filters['estado_comite']]);
            }
        }

        if (($filters['estado_auditoria'] ?? '') === 'vencida') {
            if ($usarDerivados) {
                $query->where('auditoria_pendiente', true);
            } else {
                $query->whereRaw($this->auditoriaPendienteSql());
            }
        } elseif (($filters['estado_auditoria'] ?? '') === 'al_dia') {
            if ($usarDerivados) {
                $query->where('auditoria_pendiente', false)->whereNotNull('Fch_Audit');
            } else {
                $query->whereRaw('NOT ('.$this->auditoriaPendienteSql().')')->whereNotNull('Fch_Audit');
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
                $query->whereRaw($this->rangoRotacionSql().' = ?', [$filters['rango_rotacion']]);
            }
        }

        if (($filters['tiempo_auditoria'] ?? '') === 'mes') {
            $query->where('Audit_Realiza_Mes', '>', 0);
        } elseif (($filters['tiempo_auditoria'] ?? '') === 'trimestre') {
            if ($usarDerivados) {
                $query->where('auditoria_pendiente', true);
            } else {
                $query->whereRaw($this->auditoriaPendienteSql());
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

    private function aplicarFiltrosAperturas(Builder $query, array $filters): void
    {
        if (($filters['almacen'] ?? '') !== '') {
            $query->whereRaw('"Nombre_Almacen" ILIKE ?', ['%'.$filters['almacen'].'%']);
        }

        if (($filters['desde'] ?? '') !== '') {
            $query->where('Fecha_Apertura', '>=', $filters['desde']);
        }

        if (($filters['hasta'] ?? '') !== '') {
            $query->where('Fecha_Apertura', '<=', $filters['hasta']);
        }
    }

    private function aplicarFiltrosMapa(Builder $query, array $filters): void
    {
        if (($filters['almacen'] ?? '') !== '') {
            $query->whereRaw('"Nombre_Almacen" ILIKE ?', ['%'.$filters['almacen'].'%']);
        }

        if (($filters['estado_geo'] ?? '') !== '') {
            if (($filters['estado_geo'] ?? '') === 'INCIDENCIAS') {
                $query->whereIn('estado_geo', ['SIN_COORDENADAS', 'FUERA_MEXICO']);

                return;
            }

            $query->where('estado_geo', $filters['estado_geo']);
        }
    }

    private function aplicarBounds(Builder $query, array $bounds, string $latColumn, string $lonColumn): void
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

    private function resumenCriticidad(Builder $query, bool $usarDerivados = false): array
    {
        $countSql = $this->factoresCriticosCountSql();

        $nivelSql = $usarDerivados
            ? "
                SUM(CASE WHEN nivel_critico = 'rojo' THEN 1 ELSE 0 END) as rojo,
                SUM(CASE WHEN nivel_critico = 'amarillo' THEN 1 ELSE 0 END) as amarillo,
                SUM(CASE WHEN nivel_critico = 'verde' THEN 1 ELSE 0 END) as verde,
            "
            : "
                SUM(CASE WHEN {$countSql} >= 4 THEN 1 ELSE 0 END) as rojo,
                SUM(CASE WHEN {$countSql} >= 2 AND {$countSql} < 4 THEN 1 ELSE 0 END) as amarillo,
                SUM(CASE WHEN {$countSql} < 2 THEN 1 ELSE 0 END) as verde,
            ";

        $row = (clone $query)->selectRaw("
            {$nivelSql}
            SUM(CASE WHEN {$this->indicadorCriticoSql('capital_bajo', $usarDerivados)} THEN 1 ELSE 0 END) as capital_bajo,
            SUM(CASE WHEN {$this->indicadorCriticoSql('capital_dictaminado_bajo', $usarDerivados)} THEN 1 ELSE 0 END) as capital_dictaminado_bajo,
            SUM(CASE WHEN {$this->indicadorCriticoSql('comite_vencido', $usarDerivados)} THEN 1 ELSE 0 END) as comite_vencido,
            SUM(CASE WHEN {$this->indicadorCriticoSql('auditoria_elevada', $usarDerivados)} THEN 1 ELSE 0 END) as auditoria_elevada,
            SUM(CASE WHEN {$this->indicadorCriticoSql('pagare_vencido', $usarDerivados)} THEN 1 ELSE 0 END) as pagare_vencido,
            SUM(CASE WHEN {$this->indicadorCriticoSql('rotacion_baja', $usarDerivados)} THEN 1 ELSE 0 END) as rotacion_baja,
            SUM(CASE WHEN {$this->indicadorCriticoSql('asamblea_pendiente', $usarDerivados)} THEN 1 ELSE 0 END) as asamblea_pendiente
        ")->first();

        $labels = $this->indicadorLabels();
        $desgloseLabels = [];
        foreach ($labels as $key => $label) {
            $count = (int) ($row->{$key} ?? 0);
            if ($count > 0) {
                $desgloseLabels[] = ['key' => $key, 'label' => $label, 'count' => $count];
            }
        }

        usort($desgloseLabels, fn (array $a, array $b) => $b['count'] <=> $a['count']);

        return [
            'rojo' => (int) ($row->rojo ?? 0),
            'amarillo' => (int) ($row->amarillo ?? 0),
            'verde' => (int) ($row->verde ?? 0),
            'desgloseLabels' => $desgloseLabels,
        ];
    }

    private function kpisAuditoria(Builder $query, bool $usarDerivados = false): array
    {
        $estadoComiteSql = $usarDerivados ? 'estado_comite' : $this->estadoComiteSql();
        $rangoRotacionSql = $usarDerivados ? 'rango_rotacion' : $this->rangoRotacionSql();
        $auditoriaPendienteSql = $usarDerivados ? 'auditoria_pendiente = true' : $this->auditoriaPendienteSql();
        $sinAuditoriaAnioSql = $this->sinAuditoriaAnioSql();

        $row = (clone $query)->selectRaw("
            SUM(CASE WHEN {$estadoComiteSql} = 'vencido' THEN 1 ELSE 0 END) as comites_vencidos,
            SUM(CASE WHEN COALESCE(\"Imp_Res_Audi_Mes\", 0) > 500000 THEN 1 ELSE 0 END) as auditoria_alta,
            SUM(CASE WHEN {$rangoRotacionSql} IN ('critico', 'cero') THEN 1 ELSE 0 END) as rotacion_baja,
            SUM(CASE WHEN {$auditoriaPendienteSql} THEN 1 ELSE 0 END) as auditoria_pendiente,
            SUM(CASE WHEN {$rangoRotacionSql} = 'cero' THEN 1 ELSE 0 END) as rotacion_cero,
            SUM(CASE WHEN {$rangoRotacionSql} = 'critico' THEN 1 ELSE 0 END) as rotacion_critico,
            SUM(CASE WHEN {$rangoRotacionSql} = 'amarillo' THEN 1 ELSE 0 END) as rotacion_amarillo,
            SUM(CASE WHEN {$rangoRotacionSql} = 'optimo' THEN 1 ELSE 0 END) as rotacion_optimo,
            SUM(CASE WHEN COALESCE(\"Audit_Realiza_Mes\", 0) > 0 THEN 1 ELSE 0 END) as auditorias_mes,
            SUM(CASE WHEN {$auditoriaPendienteSql} THEN 1 ELSE 0 END) as sin_auditoria_trimestre,
            SUM(CASE WHEN {$sinAuditoriaAnioSql} THEN 1 ELSE 0 END) as sin_auditoria_anio
        ")->first();

        return [
            'comitesVencidos' => (int) ($row->comites_vencidos ?? 0),
            'auditoriaAlta' => (int) ($row->auditoria_alta ?? 0),
            'rotacionBaja' => (int) ($row->rotacion_baja ?? 0),
            'auditoriaPendiente' => (int) ($row->auditoria_pendiente ?? 0),
            'rotacionCero' => (int) ($row->rotacion_cero ?? 0),
            'rotacionCritico' => (int) ($row->rotacion_critico ?? 0),
            'rotacionAmarillo' => (int) ($row->rotacion_amarillo ?? 0),
            'rotacionOptimo' => (int) ($row->rotacion_optimo ?? 0),
            'auditoriasMes' => (int) ($row->auditorias_mes ?? 0),
            'sinAuditoriaTrimestre' => (int) ($row->sin_auditoria_trimestre ?? 0),
            'sinAuditoriaAnio' => (int) ($row->sin_auditoria_anio ?? 0),
        ];
    }

    private function kpisAperturas(Builder $query): array
    {
        $row = (clone $query)->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN \"Fecha_Apertura\" >= DATE_TRUNC('month', CURRENT_DATE) THEN 1 ELSE 0 END) as este_mes,
            SUM(CASE WHEN \"Fecha_Apertura\" >= DATE_TRUNC('year', CURRENT_DATE) THEN 1 ELSE 0 END) as este_anio,
            SUM(CASE WHEN \"Fecha_Apertura\" IS NULL THEN 1 ELSE 0 END) as sin_fecha
        ")->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'esteMes' => (int) ($row->este_mes ?? 0),
            'esteAnio' => (int) ($row->este_anio ?? 0),
            'sinFecha' => (int) ($row->sin_fecha ?? 0),
        ];
    }

    private function sinConectividadCount(Builder $query): int
    {
        return (clone $query)
            ->where(function ($query) {
                $query->whereNull('TELEFONIA')->orWhere('TELEFONIA', '!=', 'S');
            })
            ->where(function ($query) {
                $query->whereNull('INTERNET')->orWhere('INTERNET', '!=', 'S');
            })
            ->where(function ($query) {
                $query->whereNull('Señal de celular')->orWhere('Señal de celular', '!=', 'S');
            })
            ->count();
    }

    private function aperturasEsteMesCount(Builder $query): int
    {
        return (clone $query)
            ->where('Fecha_Apertura', '>=', now()->startOfMonth()->toDateString())
            ->where('Fecha_Apertura', '<=', now()->endOfMonth()->toDateString())
            ->count();
    }

    private function geoStats(Builder $query, bool $usarDerivados = false): array
    {
        if ($usarDerivados) {
            $row = (clone $query)->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN estado_geo = 'OK' THEN 1 ELSE 0 END) as ok,
                SUM(CASE WHEN estado_geo = 'SIN_COORDENADAS' THEN 1 ELSE 0 END) as sin_coordenadas,
                SUM(CASE WHEN estado_geo = 'FUERA_MEXICO' THEN 1 ELSE 0 END) as fuera_mexico,
                SUM(CASE WHEN estado_geo = 'FUERA_ESTADO' THEN 1 ELSE 0 END) as fuera_estado
            ")->first();

            $sinCoordenadas = (int) ($row->sin_coordenadas ?? 0);
            $fueraMexico = (int) ($row->fuera_mexico ?? 0);
            $fueraEstado = (int) ($row->fuera_estado ?? 0);

            return [
                'OK' => (int) ($row->ok ?? 0),
                'SIN_COORDENADAS' => $sinCoordenadas,
                'FUERA_MEXICO' => $fueraMexico,
                'FUERA_ESTADO' => $fueraEstado,
                'conCoordenadas' => (int) ($row->total ?? 0) - $sinCoordenadas,
                'sinCoordenadas' => $sinCoordenadas,
                'incidencias' => $sinCoordenadas + $fueraMexico,
            ];
        }

        $row = (clone $query)->selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN \"Latitud\" IS NOT NULL AND \"Longitud\" IS NOT NULL AND \"Latitud\" != '0' AND \"Longitud\" != '0' THEN 1 ELSE 0 END) as con_coordenadas,
            SUM(CASE WHEN \"Latitud\" IS NULL OR \"Longitud\" IS NULL OR \"Latitud\" = '0' OR \"Longitud\" = '0' THEN 1 ELSE 0 END) as sin_coordenadas
        ")->first();

        $sinCoordenadas = (int) ($row->sin_coordenadas ?? 0);

        return [
            'OK' => (int) ($row->con_coordenadas ?? 0),
            'SIN_COORDENADAS' => $sinCoordenadas,
            'FUERA_MEXICO' => 0,
            'FUERA_ESTADO' => 0,
            'conCoordenadas' => (int) ($row->con_coordenadas ?? 0),
            'sinCoordenadas' => $sinCoordenadas,
            'incidencias' => $sinCoordenadas,
        ];
    }

    private function aperturasKpiDashboard(Builder $query): array
    {
        $row = (clone $query)->selectRaw("
            SUM(CASE WHEN \"Fecha_Apertura\" IS NOT NULL THEN 1 ELSE 0 END) as total,
            SUM(CASE WHEN \"Fecha_Apertura\" >= DATE_TRUNC('year', CURRENT_DATE) THEN 1 ELSE 0 END) as este_anio
        ")->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'esteAnio' => (int) ($row->este_anio ?? 0),
        ];
    }

    private function aperturasPorMes(Builder $query): array
    {
        $nombres = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $meses = [];
        $now = now();
        for ($i = 11; $i >= 0; $i--) {
            $date = (clone $now)->subMonths($i);
            $meses[$date->format('Y-m')] = ['label' => $nombres[(int) $date->format('n') - 1], 'count' => 0];
        }

        $rows = (clone $query)
            ->selectRaw('TO_CHAR("Fecha_Apertura", \'YYYY-MM\') as mes, COUNT(*) as total')
            ->whereNotNull('Fecha_Apertura')
            ->where('Fecha_Apertura', '>=', now()->subMonths(11)->startOfMonth()->toDateString())
            ->groupBy('mes')
            ->pluck('total', 'mes');

        foreach ($rows as $mes => $total) {
            if (isset($meses[$mes])) {
                $meses[$mes]['count'] = (int) $total;
            }
        }

        return array_values($meses);
    }

    private function indicadorCriticoSql(string $indicador, bool $usarDerivados = false): ?string
    {
        if ($usarDerivados) {
            return [
                'capital_bajo' => 'COALESCE("Cap_Tot", 0) > 0 AND COALESCE("Cap_Tot", 0) <= 20000',
                'capital_dictaminado_bajo' => 'COALESCE("Cap_Dic", 0) > 0 AND COALESCE("Cap_Dic", 0) <= 20000',
                'comite_vencido' => "estado_comite = 'vencido'",
                'auditoria_elevada' => 'COALESCE("Imp_Res_Audi_Mes", 0) > 500000',
                'pagare_vencido' => '"Pagare_Fecha" IS NOT NULL AND "Pagare_Fecha" <= CURRENT_DATE - INTERVAL \'1 year\'',
                'rotacion_baja' => "rango_rotacion IN ('cero', 'critico')",
                'asamblea_pendiente' => 'COALESCE("Asam_Prog_Mes", 0) > 0 AND COALESCE("Asam_Real_Mes", 0) = 0',
            ][$indicador] ?? null;
        }

        return [
            'capital_bajo' => 'COALESCE("Cap_Tot", 0) > 0 AND COALESCE("Cap_Tot", 0) <= 20000',
            'capital_dictaminado_bajo' => 'COALESCE("Cap_Dic", 0) > 0 AND COALESCE("Cap_Dic", 0) <= 20000',
            'comite_vencido' => $this->estadoComiteSql().' = \'vencido\'',
            'auditoria_elevada' => 'COALESCE("Imp_Res_Audi_Mes", 0) > 500000',
            'pagare_vencido' => '"Pagare_Fecha" IS NOT NULL AND "Pagare_Fecha" <= CURRENT_DATE - INTERVAL \'1 year\'',
            'rotacion_baja' => $this->rangoRotacionSql().' IN (\'cero\', \'critico\')',
            'asamblea_pendiente' => 'COALESCE("Asam_Prog_Mes", 0) > 0 AND COALESCE("Asam_Real_Mes", 0) = 0',
        ][$indicador] ?? null;
    }

    private function estadoComiteSql(): string
    {
        return "CASE
            WHEN \"Vigencia\" IS NULL THEN 'sin_fecha'
            WHEN \"Vigencia\" <= CURRENT_DATE THEN 'vencido'
            WHEN \"Vigencia\" <= CURRENT_DATE + INTERVAL '30 days' THEN 'proximo_a_vencer'
            ELSE 'vigente'
        END";
    }

    private function rangoRotacionSql(): string
    {
        return "CASE
            WHEN COALESCE(\"Cap_Dic\", 0) <= 0 OR COALESCE(\"Vta_Mes\", 0) = 0 THEN 'cero'
            WHEN COALESCE(\"Vta_Mes\", 0) / NULLIF(\"Cap_Dic\", 0) < 0.5 THEN 'critico'
            WHEN COALESCE(\"Vta_Mes\", 0) / NULLIF(\"Cap_Dic\", 0) < 1 THEN 'amarillo'
            ELSE 'optimo'
        END";
    }

    private function auditoriaPendienteSql(): string
    {
        return '"Fch_Audit" IS NULL OR "Fch_Audit" <= CURRENT_DATE - INTERVAL \'3 months\'';
    }

    private function sinAuditoriaAnioSql(): string
    {
        return '"Fch_Audit" IS NULL OR "Fch_Audit" <= CURRENT_DATE - INTERVAL \'1 year\'';
    }

    private function factoresCriticosCountSql(): string
    {
        return implode(' + ', array_map(
            fn (string $indicador) => 'CASE WHEN '.$this->indicadorCriticoSql($indicador).' THEN 1 ELSE 0 END',
            array_keys($this->indicadorLabels())
        ));
    }

    private function nivelCriticoSql(): string
    {
        $countSql = $this->factoresCriticosCountSql();

        return "CASE
            WHEN {$countSql} >= 4 THEN 'rojo'
            WHEN {$countSql} >= 2 THEN 'amarillo'
            ELSE 'verde'
        END";
    }

    private function indicadorLabels(): array
    {
        return [
            'capital_bajo' => '💰 Capital total bajo',
            'capital_dictaminado_bajo' => '🏛️ Capital Bienestar bajo',
            'comite_vencido' => '📅 Comité vencido',
            'auditoria_elevada' => '🔍 Auditoría > $500k',
            'pagare_vencido' => '📄 Pagaré vencido',
            'rotacion_baja' => '📉 Rotación baja',
            'asamblea_pendiente' => '🗳️ Asamblea pendiente',
        ];
    }

    private function camposIncompletosSql(array $columns): string
    {
        return collect($columns)
            ->reject(fn (string $column) => str_contains($column, 'Sup_CRA'))
            ->map(fn (string $column) => 'NULLIF(TRIM(COALESCE("'.$column.'"::text, \'\')), \'\') IS NULL OR TRIM(COALESCE("'.$column.'"::text, \'\')) = \'0\'')
            ->implode(' OR ');
    }

    /**
     * @param  array{column?: string|null, direction?: string}  $sort
     * @param  array<int, string>  $allowedColumns
     * @param  array<string, string>  $expressionColumns
     */
    private function aplicarOrdenTabla(Builder $query, array $sort, array $allowedColumns, array $expressionColumns = []): void
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

    /**
     * @param  array{column?: string|null, direction?: string}  $sort
     * @param  array<int, string>  $columns
     */
    private function aplicarOrdenCriticidad(Builder $query, array $sort, array $columns, bool $usarDerivados): void
    {
        $countSql = $usarDerivados ? 'factores_criticos_count' : $this->factoresCriticosCountSql();
        $levelSql = $usarDerivados
            ? "CASE nivel_critico WHEN 'verde' THEN 1 WHEN 'amarillo' THEN 2 WHEN 'rojo' THEN 3 ELSE 0 END"
            : "CASE ({$this->nivelCriticoSql()}) WHEN 'verde' THEN 1 WHEN 'amarillo' THEN 2 WHEN 'rojo' THEN 3 ELSE 0 END";

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

    /**
     * @param  array{column?: string|null, direction?: string}  $sort
     * @param  array<int, string>  $columns
     */
    private function aplicarOrdenAuditoria(Builder $query, array $sort, array $columns, bool $usarDerivados): void
    {
        $auditoriaPendienteSql = $usarDerivados ? 'CASE WHEN auditoria_pendiente THEN 1 ELSE 0 END' : 'CASE WHEN '.$this->auditoriaPendienteSql().' THEN 1 ELSE 0 END';
        $estadoComiteSql = $usarDerivados
            ? "CASE estado_comite WHEN 'vigente' THEN 1 WHEN 'proximo_a_vencer' THEN 2 WHEN 'vencido' THEN 3 ELSE 0 END"
            : "CASE ({$this->estadoComiteSql()}) WHEN 'vigente' THEN 1 WHEN 'proximo_a_vencer' THEN 2 WHEN 'vencido' THEN 3 ELSE 0 END";
        $rangoRotacionSql = $usarDerivados
            ? "CASE rango_rotacion WHEN 'cero' THEN 0 WHEN 'critico' THEN 1 WHEN 'amarillo' THEN 2 WHEN 'optimo' THEN 3 ELSE 0 END"
            : "CASE ({$this->rangoRotacionSql()}) WHEN 'cero' THEN 0 WHEN 'critico' THEN 1 WHEN 'amarillo' THEN 2 WHEN 'optimo' THEN 3 ELSE 0 END";
        $rotacionSql = 'CASE WHEN COALESCE("Cap_Dic", 0) > 0 THEN COALESCE("Vta_Mes", 0) / NULLIF("Cap_Dic", 0) ELSE 0 END';
        $nivelSql = $usarDerivados
            ? "CASE nivel_critico WHEN 'verde' THEN 1 WHEN 'amarillo' THEN 2 WHEN 'rojo' THEN 3 ELSE 0 END"
            : "CASE ({$this->nivelCriticoSql()}) WHEN 'verde' THEN 1 WHEN 'amarillo' THEN 2 WHEN 'rojo' THEN 3 ELSE 0 END";

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

    /**
     * @param  array{column?: string|null, direction?: string}  $sort
     * @param  array<int, string>  $columns
     */
    private function aplicarOrdenAperturas(Builder $query, array $sort, array $columns): void
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

    private function sinCapitalSql(): string
    {
        return '"Cap_Tot" IS NULL OR COALESCE("Cap_Tot", 0) = 0';
    }

    private function rowToCriticalStore(object $row, array $columns): array
    {
        $store = $this->rowToStore($row, $columns);
        $conditions = [];
        foreach (array_keys($this->indicadorLabels()) as $key) {
            $conditions[$key] = $this->rowMatchesIndicador($row, $key);
        }

        $store['_critico'] = [
            'conditions' => $conditions,
            'labels' => collect($this->indicadorLabels())->map(fn (string $label) => ['label' => $label, 'detail' => ''])->all(),
            'count' => count(array_filter($conditions)),
            'level' => $this->levelFromCriticalCount(count(array_filter($conditions))),
        ];

        return $store;
    }

    private function rowToAuditStore(object $row, array $columns): array
    {
        $store = $this->rowToStore($row, $columns);
        $fchAudit = $row->Fch_Audit ?? null;
        $mesesSinAuditoria = $fchAudit ? Carbon::parse($fchAudit)->diffInMonths(now()) : null;
        $impuesto = (float) ($row->Imp_Res_Audi_Mes ?? 0);
        $capDic = (float) ($row->Cap_Dic ?? 0);
        $vtaMes = (float) ($row->Vta_Mes ?? 0);
        $rotacion = $capDic > 0 ? $vtaMes / $capDic : 0;
        $estadoComite = $this->estadoComiteFromDate($row->Vigencia ?? null);
        $rangoRotacion = $this->rangoRotacionFromValues($capDic, $vtaMes);
        $auditoriaPendiente = $fchAudit === null || Carbon::parse($fchAudit)->lte(now()->subMonths(3));
        $conditions = [];
        if ($estadoComite === 'vencido') {
            $conditions[] = 'comite_vencido';
        }
        if ($impuesto > 500000) {
            $conditions[] = 'auditoria_alta';
        }
        if (in_array($rangoRotacion, ['cero', 'critico'], true)) {
            $conditions[] = 'rotacion_baja';
        }
        if ($auditoriaPendiente) {
            $conditions[] = 'auditoria_pendiente';
        }

        $store['_audit'] = [
            'level' => $this->levelFromAuditCount(count($conditions)),
            'conditions' => $conditions,
            'estadoComite' => $estadoComite,
            'vigencia' => $this->valorAString($row->Vigencia ?? null),
            'impuesto' => $impuesto,
            'rotacion' => $rotacion,
            'fchAudit' => $this->valorAString($fchAudit),
            'mesesSinAuditoria' => $mesesSinAuditoria,
            'rangoRotacion' => $rangoRotacion,
            'auditRealizada' => (int) ($row->Audit_Realiza_Mes ?? 0),
            'sinAuditoriaAnio' => $fchAudit === null || Carbon::parse($fchAudit)->lte(now()->subYear()),
            'auditoriaPendiente' => $auditoriaPendiente,
        ];

        return $store;
    }

    private function rowToAperturaStore(object $row, array $columns): array
    {
        $store = $this->rowToStore($row, $columns);
        $fecha = $row->Fecha_Apertura ?? null;
        $store['_fecha_apertura'] = $fecha ? Carbon::parse($fecha)->toDateString() : null;
        $store['_antiguedad'] = $fecha ? ((int) Carbon::parse($fecha)->diffInMonths(now())).' meses' : '—';

        return $store;
    }

    private function rowToGeoStore(object $row, array $columns): array
    {
        $store = $this->rowToStore($row, $columns);
        $store['_geo'] = $this->geo()->evaluarGeo($store);
        $store['_cxc'] = [
            'esTiendaBienestar' => (bool) ($row->es_tienda_salud_bienestar ?? false),
            'esTiendaSaludBienestar' => (bool) ($row->es_tienda_salud_bienestar ?? false),
        ];

        return $store;
    }

    private function selectMapaColumns(Builder $query, array $columns): Builder
    {
        return $this->addTiendaSaludFlag(
            $query->select(array_values(array_unique(array_merge($columns, ['estado_geo'])))),
        );
    }

    private function addTiendaSaludFlag(Builder $query): Builder
    {
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

    private function filtrarGeoCalculado(array $rows, string $estadoGeo): array
    {
        if ($estadoGeo === '') {
            return $rows;
        }

        if ($estadoGeo === 'INCIDENCIAS') {
            return array_values(array_filter($rows, fn (array $row) => in_array($row['_geo']['status'] ?? '', ['SIN_COORDENADAS', 'FUERA_MEXICO'], true)));
        }

        return array_values(array_filter($rows, fn (array $row) => ($row['_geo']['status'] ?? '') === $estadoGeo));
    }

    private function geo(): ServicioGeo
    {
        return app(ServicioGeo::class);
    }

    private function rowMatchesIndicador(object $row, string $key): bool
    {
        $capTot = (float) ($row->Cap_Tot ?? 0);
        $capDic = (float) ($row->Cap_Dic ?? 0);
        $vtaMes = (float) ($row->Vta_Mes ?? 0);

        return match ($key) {
            'capital_bajo' => $capTot > 0 && $capTot <= 20000,
            'capital_dictaminado_bajo' => $capDic > 0 && $capDic <= 20000,
            'comite_vencido' => ! empty($row->Vigencia) && Carbon::parse($row->Vigencia)->isPast(),
            'auditoria_elevada' => (float) ($row->Imp_Res_Audi_Mes ?? 0) > 500000,
            'pagare_vencido' => ! empty($row->Pagare_Fecha) && Carbon::parse($row->Pagare_Fecha)->addYear()->isPast(),
            'rotacion_baja' => $capDic <= 0 || ($vtaMes / $capDic) < 0.5,
            'asamblea_pendiente' => (int) ($row->Asam_Prog_Mes ?? 0) > 0 && (int) ($row->Asam_Real_Mes ?? 0) === 0,
            default => false,
        };
    }

    private function levelFromCriticalCount(int $count): string
    {
        if ($count >= 4) {
            return 'rojo';
        }

        if ($count >= 2) {
            return 'amarillo';
        }

        return 'verde';
    }

    private function levelFromAuditCount(int $count): string
    {
        if ($count >= 2) {
            return 'rojo';
        }

        if ($count >= 1) {
            return 'amarillo';
        }

        return 'verde';
    }

    private function estadoComiteFromDate(mixed $vigencia): string
    {
        if (empty($vigencia)) {
            return 'sin_fecha';
        }

        $date = Carbon::parse($vigencia);
        if ($date->isPast()) {
            return 'vencido';
        }

        if ($date->lte(now()->addDays(30))) {
            return 'proximo_a_vencer';
        }

        return 'vigente';
    }

    private function rangoRotacionFromValues(float $capDic, float $vtaMes): string
    {
        if ($capDic <= 0) {
            return 'cero';
        }

        $rotacion = $vtaMes / $capDic;
        if ($rotacion < 0.5) {
            return 'critico';
        }

        if ($rotacion < 1) {
            return 'amarillo';
        }

        return 'optimo';
    }

    private function trackedDirectorioColumns(): array
    {
        return [
            'TELEFONIA', 'CORREO', 'Señal de celular', 'Compañía', 'INTERNET',
            'Vta_Mes', 'VtaNeta_Mes', 'Cap_Tot', 'Cap_Com', 'Cap_Dic',
            'Pagare_Monto', 'Pagare_Fecha', 'Fec_CRA', 'Vigencia', 'Fch_Audit', 'Imp_Res_Audi_Mes',
            'Audit_Realiza_Mes', 'Latitud', 'Longitud', 'Direccion',
            'Nom_Pre_CRA', 'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA', 'Nom_Sec_Sup_CRA',
            'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA',
        ];
    }

    private function rowToStore(object $row, array $columns): array
    {
        $store = [];
        foreach ($columns as $column) {
            $store[$column] = $this->valorAString($row->{$column} ?? null);
        }
        $store['es_tienda_salud_bienestar'] = ! empty($row->es_tienda_salud_bienestar ?? false);

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
