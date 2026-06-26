<?php

namespace App\Servicios;

use App\Presenters\PresentadorTiendas;
use App\Servicios\Modulos\ServicioConsultasTiendas;
use Illuminate\Support\Facades\Log;

class ServicioPostgresql
{
    private ?string $ultimoError = null;

    private array $derivadosCompletosCache = [];

    private array $trackedDirectorioColumns = [
        'TELEFONIA', 'CORREO', 'Señal de celular', 'Compañía', 'INTERNET',
        'Vta_Mes', 'VtaNeta_Mes', 'Cap_Tot', 'Cap_Com', 'Cap_Dic',
        'Pagare_Monto', 'Pagare_Fecha', 'Fec_CRA', 'Vigencia', 'Fch_Audit', 'Imp_Res_Audi_Mes',
        'Audit_Realiza_Mes', 'Latitud', 'Longitud', 'Direccion',
        'Nom_Pre_CRA', 'Nom_Pre_Sup_CRA', 'Nom_Sec_CRA', 'Nom_Sec_Sup_CRA',
        'Nom_Tes_CRA', 'Nom_Vcv_CRA', 'Nom_Voc_Gen_CRA',
    ];

    public function __construct(
        private ?ServicioIndicadorCriticidad $indicadores = null,
        private ?ServicioConsultasTiendas $consultas = null,
        private ?PresentadorTiendas $presentador = null,
        private ?ServicioKpiTiendas $kpiTiendas = null,
        private ?ServicioMapaTiendas $mapaTiendas = null,
        private ?ServicioJerarquiaRegional $jerarquiaRegional = null,
        private ?ServicioExportacionTiendas $exportacionTiendas = null,
        private ?ServicioDashboardMetricas $dashboardMetricas = null,
    ) {
        $this->indicadores ??= app(ServicioIndicadorCriticidad::class);
        $this->consultas ??= app(ServicioConsultasTiendas::class);
        $this->presentador ??= app(PresentadorTiendas::class);
        $this->kpiTiendas ??= app(ServicioKpiTiendas::class);
        $this->mapaTiendas ??= app(ServicioMapaTiendas::class);
        $this->jerarquiaRegional ??= app(ServicioJerarquiaRegional::class);
        $this->exportacionTiendas ??= app(ServicioExportacionTiendas::class);
        $this->dashboardMetricas ??= app(ServicioDashboardMetricas::class);
    }

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

    public function obtenerConectividadPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $sort = []): array
    {
        $this->ultimoError = null;

        try {
            $conn = $this->consultas->conexion();
            $base = $conn->table('tiendas');
            $this->consultas->aplicarPeriodoActivo($base, $regionFilters);
            $this->consultas->aplicarFiltroRegional($base, $regionFilters);

            $filtered = clone $base;
            $this->consultas->aplicarFiltrosConectividad($filtered, $filters);
            $this->consultas->aplicarFiltroTiendaSalud($filtered, $filters['tienda_salud'] ?? '');

            $selectColumns = [
                'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET',
            ];

            $rowsQuery = $this->consultas->addTiendaSaludFlag((clone $filtered)->select($selectColumns));
            $this->consultas->aplicarOrdenTabla($rowsQuery, $sort, $selectColumns);

            $rows = $rowsQuery->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get()
                ->map(fn ($row) => $this->presentador->rowToStore($row, $selectColumns))
                ->all();

            return [
                'rows' => $rows,
                'total' => (clone $base)->count(),
                'filtered' => (clone $filtered)->count(),
                'kpis' => $this->kpiTiendas->kpisConectividad(clone $filtered),
                'companias' => $this->kpiTiendas->companiasConectividad(clone $base),
            ];
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            Log::error('[Postgresql] obtenerConectividadPaginada: '.$e->getMessage());

            return ['rows' => [], 'total' => 0, 'filtered' => 0, 'kpis' => [], 'companias' => []];
        }
    }

    public function obtenerDirectorioPaginado(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $trackedColumns, array $sort = []): array
    {
        $this->ultimoError = null;

        try {
            $conn = $this->consultas->conexion();
            $base = $conn->table('tiendas');
            $this->consultas->aplicarPeriodoActivo($base, $regionFilters);
            $this->consultas->aplicarFiltroRegional($base, $regionFilters);

            $filtered = clone $base;
            $this->consultas->aplicarFiltrosDirectorio($filtered, $filters, $trackedColumns);
            $this->consultas->aplicarFiltroTiendaSalud($filtered, $filters['tienda_salud'] ?? '');

            $rowsQuery = $this->consultas->addTiendaSaludFlag((clone $filtered)->select($columns));
            $this->consultas->aplicarOrdenTabla($rowsQuery, $sort, $columns);

            $rows = $rowsQuery->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get()
                ->map(fn ($row) => $this->presentador->rowToStore($row, $columns))
                ->all();

            return [
                'rows' => $rows,
                'total' => (clone $base)->count(),
                'filtered' => (clone $filtered)->count(),
                'stats' => $this->kpiTiendas->statsDirectorio(clone $base, $trackedColumns),
            ];
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            Log::error('[Postgresql] obtenerDirectorioPaginado: '.$e->getMessage());

            return ['rows' => [], 'total' => 0, 'filtered' => 0, 'stats' => []];
        }
    }

    public function obtenerCriticidadPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $sort = []): array
    {
        $this->ultimoError = null;

        try {
            $conn = $this->consultas->conexion();
            $base = $conn->table('tiendas');
            $this->consultas->aplicarPeriodoActivo($base, $regionFilters);
            $this->consultas->aplicarFiltroRegional($base, $regionFilters);
            $usarDerivados = $this->derivadosCompletos($regionFilters);

            $filtered = clone $base;
            $this->consultas->aplicarFiltrosCriticidad($filtered, $filters, $usarDerivados);
            $this->consultas->aplicarFiltroTiendaSalud($filtered, $filters['tienda_salud'] ?? '');

            $selectColumns = array_values(array_unique(array_merge($columns, ['nivel_critico', 'factores_criticos_count'])));
            $rowsQuery = $this->consultas->addTiendaSaludFlag((clone $filtered)->select($selectColumns));
            $this->consultas->aplicarOrdenCriticidad($rowsQuery, $sort, $columns, $usarDerivados);

            $rows = $rowsQuery->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get()
                ->map(fn ($row) => $this->presentador->rowToCriticalStore($row, $columns))
                ->all();

            return [
                'rows' => $rows,
                'total' => (clone $base)->count(),
                'filtered' => (clone $filtered)->count(),
                'summary' => $this->kpiTiendas->resumenCriticidad(clone $base, $usarDerivados),
            ];
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            Log::error('[Postgresql] obtenerCriticidadPaginada: '.$e->getMessage());

            return ['rows' => [], 'total' => 0, 'filtered' => 0, 'summary' => []];
        }
    }

    public function obtenerAuditoriaPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $sort = []): array
    {
        $this->ultimoError = null;

        try {
            $conn = $this->consultas->conexion();
            $base = $conn->table('tiendas');
            $this->consultas->aplicarPeriodoActivo($base, $regionFilters);
            $this->consultas->aplicarFiltroRegional($base, $regionFilters);
            $usarDerivados = $this->derivadosCompletos($regionFilters);

            $filtered = clone $base;
            $this->consultas->aplicarFiltrosAuditoria($filtered, $filters, $usarDerivados);
            $this->consultas->aplicarFiltroTiendaSalud($filtered, $filters['tienda_salud'] ?? '');

            $selectColumns = array_values(array_unique(array_merge($columns, ['nivel_critico', 'estado_comite', 'rango_rotacion', 'auditoria_pendiente'])));
            $rowsQuery = $this->consultas->addTiendaSaludFlag((clone $filtered)->select($selectColumns));
            $this->consultas->aplicarOrdenAuditoria($rowsQuery, $sort, $columns, $usarDerivados);

            $rows = $rowsQuery->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get()
                ->map(fn ($row) => $this->presentador->rowToAuditStore($row, $columns))
                ->all();

            return [
                'rows' => $rows,
                'total' => (clone $base)->count(),
                'filtered' => (clone $filtered)->count(),
                'kpis' => $this->kpiTiendas->kpisAuditoria(clone $base, $usarDerivados),
            ];
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            Log::error('[Postgresql] obtenerAuditoriaPaginada: '.$e->getMessage());

            return ['rows' => [], 'total' => 0, 'filtered' => 0, 'kpis' => []];
        }
    }

    public function obtenerAperturasPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $sort = []): array
    {
        $this->ultimoError = null;

        try {
            $conn = $this->consultas->conexion();
            $base = $conn->table('tiendas');
            $this->consultas->aplicarPeriodoActivo($base, $regionFilters);
            $this->consultas->aplicarFiltroRegional($base, $regionFilters);

            $filtered = clone $base;
            $this->consultas->aplicarFiltrosAperturas($filtered, $filters);
            $this->consultas->aplicarFiltroTiendaSalud($filtered, $filters['tienda_salud'] ?? '');

            $rowsQuery = $this->consultas->addTiendaSaludFlag((clone $filtered)->select($columns));
            $this->consultas->aplicarOrdenAperturas($rowsQuery, $sort, $columns);

            $rows = $rowsQuery->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get()
                ->map(fn ($row) => $this->presentador->rowToAperturaStore($row, $columns))
                ->all();

            return [
                'rows' => $rows,
                'total' => (clone $base)->count(),
                'filtered' => (clone $filtered)->count(),
                'kpis' => $this->kpiTiendas->kpisAperturas(clone $filtered),
            ];
        } catch (\Throwable $e) {
            $this->ultimoError = $e->getMessage();
            Log::error('[Postgresql] obtenerAperturasPaginada: '.$e->getMessage());

            return ['rows' => [], 'total' => 0, 'filtered' => 0, 'kpis' => []];
        }
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

        return $this->dashboardMetricas->obtenerDashboardMetricas($regionFilters, $usarDerivados, $this->trackedDirectorioColumns);
    }

    public function exportarTiendas(array $regionFilters, array $filters, array $columns, string $module): \Generator
    {
        return $this->exportacionTiendas->exportarTiendas($regionFilters, $filters, $columns, $module, $this->derivadosCompletos($regionFilters), $this->trackedDirectorioColumns);
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
