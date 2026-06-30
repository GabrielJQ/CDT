<?php

namespace App\Servicios;

use App\Presenters\PresentadorTiendas;
use App\Servicios\Modulos\ServicioConsultasTiendas;
use Illuminate\Support\Facades\Log;

class ServicioPostgresql
{
    private const TRACKED_DIRECTORIO_COLUMNS = [
        'TELEFONIA', 'CORREO', 'Señal de celular', 'Compañía', 'INTERNET',
        'Vta_Mes', 'VtaNeta_Mes', 'Cap_Tot', 'Cap_Com', 'Cap_Dic',
        'Pagare_Monto', 'Pagare_Fecha', 'Fec_CRA', 'Vigencia', 'Fch_Audit', 'Imp_Res_Audi_Mes',
        'Audit_Realiza_Mes', 'Latitud', 'Longitud', 'Direccion',
        'Nom_Pre_CRA', 'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA', 'Nom_Sec_Sup_CRA',
        'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA',
    ];

    private array $derivadosCompletosCache = [];

    public function __construct(
        private ServicioConsultasTiendas $consultas,
        private PresentadorTiendas $presentador,
        private ServicioKpiTiendas $kpiTiendas,
        private ServicioMapaTiendas $mapaTiendas,
        private ServicioJerarquiaRegional $jerarquiaRegional,
        private ServicioExportacionTiendas $exportacionTiendas,
        private ServicioDashboardMetricas $dashboardMetricas,
    ) {}

    public function obtenerTiendas(array $filters = [], ?array $columns = null): array
    {
        try {
            return $this->fetchDesdePostgres($filters, $columns);
        } catch (\Throwable $e) {
            Log::error('[Postgresql] '.$e->getMessage());

            return [];
        }
    }

    private function fetchDesdePostgres(array $filters = [], ?array $columns = null): array
    {
        $conn = $this->consultas->conexion();
        $countQuery = $conn->table('tiendas');
        $this->consultas->aplicarPeriodoActivo($countQuery, $filters);
        $count = $countQuery->count();
        if ($count === 0) {
            throw new \RuntimeException('La tabla tiendas está vacía en PostgreSQL');
        }

        $reverseMap = $this->consultas->reverseMap();
        $csvColumns = $columns ? array_values(array_intersect($columns, array_keys($reverseMap))) : array_keys($reverseMap);
        if ($csvColumns === []) {
            $csvColumns = array_keys($reverseMap);
        }
        $dbColumns = array_values(array_unique(array_map(fn (string $csvColumn) => $reverseMap[$csvColumn], $csvColumns)));

        $query = $conn->table('tiendas')->select($dbColumns);
        $this->consultas->aplicarPeriodoActivo($query, $filters);

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
                    $store[$csvColumn] = $this->presentador->valorAString($value);
                }
                $stores[] = $store;
            }
        });

        return $stores;
    }

    public function obtenerJerarquiaRegional(): array
    {
        try {
            return $this->jerarquiaRegional->obtenerJerarquiaRegional();
        } catch (\Throwable $e) {
            Log::error('[Postgresql] obtenerJerarquiaRegional: '.$e->getMessage());

            return [];
        }
    }

    private function paginateModule(
        array $regionFilters,
        array $filters,
        int $page,
        int $perPage,
        array $columns,
        array $sort,
        array $extraColumns,
        string $logTag,
        callable $applyFilters,
        callable $applyOrder,
        callable $mapRow,
        callable $computeExtras,
        array $errorExtras,
    ): array {
        try {
            $conn = $this->consultas->conexion();
            $base = $conn->table('tiendas');
            $this->consultas->aplicarPeriodoActivo($base, $regionFilters);
            $this->consultas->aplicarFiltroRegional($base, $regionFilters);

            $filtered = clone $base;
            $applyFilters($filtered, $filters);
            $this->consultas->aplicarFiltroTiendaSalud($filtered, $filters['tienda_salud'] ?? '');

            $selectColumns = $extraColumns
                ? array_values(array_unique(array_merge($columns, $extraColumns)))
                : $columns;

            $rowsQuery = $this->consultas->addTiendaSaludFlag((clone $filtered)->select($selectColumns));
            $applyOrder($rowsQuery, $sort);

            $rows = $rowsQuery->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get()
                ->map(fn ($row) => $mapRow($row))
                ->all();

            return [
                'rows' => $rows,
                'total' => (clone $base)->count(),
                'filtered' => (clone $filtered)->count(),
            ] + $computeExtras(clone $base, clone $filtered);
        } catch (\Throwable $e) {
            Log::error("[Postgresql] {$logTag}: ".$e->getMessage());

            return ['rows' => [], 'total' => 0, 'filtered' => 0] + $errorExtras;
        }
    }

    public function obtenerConectividadPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $sort = []): array
    {
        $columns = [
            'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET',
        ];

        return $this->paginateModule(
            regionFilters: $regionFilters,
            filters: $filters,
            page: $page,
            perPage: $perPage,
            columns: $columns,
            sort: $sort,
            extraColumns: [],
            logTag: 'obtenerConectividadPaginada',
            applyFilters: fn ($q, $f) => $this->consultas->aplicarFiltrosConectividad($q, $f),
            applyOrder: fn ($q, $s) => $this->consultas->aplicarOrdenTabla($q, $s, $columns),
            mapRow: fn ($row) => $this->presentador->rowToStore($row, $columns),
            computeExtras: fn ($base, $filtered) => [
                'kpis' => $this->kpiTiendas->kpisConectividad($filtered),
                'companias' => $this->kpiTiendas->companiasConectividad($base),
            ],
            errorExtras: ['kpis' => [], 'companias' => []],
        );
    }

    public function obtenerDirectorioPaginado(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $trackedColumns, array $sort = []): array
    {
        return $this->paginateModule(
            regionFilters: $regionFilters,
            filters: $filters,
            page: $page,
            perPage: $perPage,
            columns: $columns,
            sort: $sort,
            extraColumns: [],
            logTag: 'obtenerDirectorioPaginado',
            applyFilters: fn ($q, $f) => $this->consultas->aplicarFiltrosDirectorio($q, $f, $trackedColumns),
            applyOrder: fn ($q, $s) => $this->consultas->aplicarOrdenTabla($q, $s, $columns),
            mapRow: fn ($row) => $this->presentador->rowToStore($row, $columns),
            computeExtras: fn ($base, $filtered) => [
                'stats' => $this->kpiTiendas->statsDirectorio($base, $trackedColumns),
            ],
            errorExtras: ['stats' => []],
        );
    }

    public function obtenerCriticidadPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $sort = []): array
    {
        $usarDerivados = $this->derivadosCompletos($regionFilters);

        return $this->paginateModule(
            regionFilters: $regionFilters,
            filters: $filters,
            page: $page,
            perPage: $perPage,
            columns: $columns,
            sort: $sort,
            extraColumns: ['nivel_critico', 'factores_criticos_count'],
            logTag: 'obtenerCriticidadPaginada',
            applyFilters: fn ($q, $f) => $this->consultas->aplicarFiltrosCriticidad($q, $f, $usarDerivados),
            applyOrder: fn ($q, $s) => $this->consultas->aplicarOrdenCriticidad($q, $s, $columns, $usarDerivados),
            mapRow: fn ($row) => $this->presentador->rowToCriticalStore($row, $columns),
            computeExtras: fn ($base, $filtered) => [
                'summary' => $this->kpiTiendas->resumenCriticidad($base, $usarDerivados),
            ],
            errorExtras: ['summary' => []],
        );
    }

    public function obtenerAuditoriaPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $sort = []): array
    {
        $usarDerivados = $this->derivadosCompletos($regionFilters);

        return $this->paginateModule(
            regionFilters: $regionFilters,
            filters: $filters,
            page: $page,
            perPage: $perPage,
            columns: $columns,
            sort: $sort,
            extraColumns: ['nivel_critico', 'estado_comite', 'rango_rotacion', 'auditoria_pendiente'],
            logTag: 'obtenerAuditoriaPaginada',
            applyFilters: fn ($q, $f) => $this->consultas->aplicarFiltrosAuditoria($q, $f, $usarDerivados),
            applyOrder: fn ($q, $s) => $this->consultas->aplicarOrdenAuditoria($q, $s, $columns, $usarDerivados),
            mapRow: fn ($row) => $this->presentador->rowToAuditStore($row, $columns),
            computeExtras: fn ($base, $filtered) => [
                'kpis' => $this->kpiTiendas->kpisAuditoria($base, $usarDerivados),
            ],
            errorExtras: ['kpis' => []],
        );
    }

    public function obtenerAperturasPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $sort = []): array
    {
        return $this->paginateModule(
            regionFilters: $regionFilters,
            filters: $filters,
            page: $page,
            perPage: $perPage,
            columns: $columns,
            sort: $sort,
            extraColumns: [],
            logTag: 'obtenerAperturasPaginada',
            applyFilters: fn ($q, $f) => $this->consultas->aplicarFiltrosAperturas($q, $f),
            applyOrder: fn ($q, $s) => $this->consultas->aplicarOrdenAperturas($q, $s, $columns),
            mapRow: fn ($row) => $this->presentador->rowToAperturaStore($row, $columns),
            computeExtras: fn ($base, $filtered) => [
                'kpis' => $this->kpiTiendas->kpisAperturas($filtered),
            ],
            errorExtras: ['kpis' => []],
        );
    }

    public function obtenerMapa(array $regionFilters, array $filters, array $columns): array
    {
        return $this->mapaTiendas->obtenerMapa($regionFilters, $filters, $columns);
    }

    public function obtenerMapaViewport(array $regionFilters, array $filters, array $bounds, array $columns, int $limit = 3000): array
    {
        return $this->mapaTiendas->obtenerMapaViewport($regionFilters, $filters, $bounds, $columns, $limit);
    }

    public function contarMapaFiltrado(array $regionFilters, array $filters): int
    {
        return $this->mapaTiendas->contarMapaFiltrado($regionFilters, $filters);
    }

    public function obtenerIncidenciasMapaPaginadas(array $regionFilters, array $filters, array $columns, ?string $sort = null, string $direction = 'asc', int $page = 1, int $perPage = 50): array
    {
        return $this->mapaTiendas->obtenerIncidenciasMapaPaginadas($regionFilters, $filters, $columns, $sort, $direction, $page, $perPage);
    }

    public function obtenerDashboardMetricas(array $regionFilters): array
    {
        $usarDerivados = $this->derivadosCompletos($regionFilters);

        return $this->dashboardMetricas->obtenerDashboardMetricas($regionFilters, $usarDerivados, self::TRACKED_DIRECTORIO_COLUMNS);
    }

    public function exportarTiendas(array $regionFilters, array $filters, array $columns, string $module): \Generator
    {
        yield from $this->exportacionTiendas->exportarTiendas($regionFilters, $filters, $columns, $module, $this->derivadosCompletos($regionFilters), self::TRACKED_DIRECTORIO_COLUMNS);
    }

    public function tieneDatos(): bool
    {
        try {
            $query = $this->consultas->conexion()->table('tiendas');
            $this->consultas->aplicarPeriodoActivo($query);

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

    public function diagnosticoDerivados(array $regionFilters = []): array
    {
        try {
            $query = $this->consultas->conexion()->table('tiendas');
            $this->consultas->aplicarPeriodoActivo($query, $regionFilters);
            $this->consultas->aplicarFiltroRegional($query, $regionFilters);
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
}
