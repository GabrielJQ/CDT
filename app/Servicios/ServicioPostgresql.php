<?php

namespace App\Servicios;

use App\Presenters\PresentadorTiendas;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
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
        private ?PresentadorTiendas $presentador = null,
        private ?ServicioKpiTiendas $kpiTiendas = null,
    ) {
        $this->indicadores ??= app(ServicioIndicadorCriticidad::class);
        $this->presentador ??= app(PresentadorTiendas::class);
        $this->kpiTiendas ??= app(ServicioKpiTiendas::class);
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
        $conn = $this->conexion();
        $countQuery = $conn->table('tiendas');
        $this->aplicarPeriodoActivo($countQuery, $filters);
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
        $this->aplicarPeriodoActivo($query, $filters);

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

    public function obtenerConectividadPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $sort = []): array
    {
        $this->ultimoError = null;

        try {
            $conn = $this->conexion();
            $base = $conn->table('tiendas');
            $this->aplicarPeriodoActivo($base, $regionFilters);
            $this->aplicarFiltroRegional($base, $regionFilters);

            $filtered = clone $base;
            $this->aplicarFiltrosConectividad($filtered, $filters);
            $this->aplicarFiltroTiendaSalud($filtered, $filters['tienda_salud'] ?? '');

            $selectColumns = [
                'Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'TELEFONIA', 'Señal de celular', 'Compañía', 'INTERNET',
            ];

            $rowsQuery = $this->addTiendaSaludFlag((clone $filtered)->select($selectColumns));
            $this->aplicarOrdenTabla($rowsQuery, $sort, $selectColumns);

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
            $conn = $this->conexion();
            $base = $conn->table('tiendas');
            $this->aplicarPeriodoActivo($base, $regionFilters);
            $this->aplicarFiltroRegional($base, $regionFilters);

            $filtered = clone $base;
            $this->aplicarFiltrosDirectorio($filtered, $filters, $trackedColumns);
            $this->aplicarFiltroTiendaSalud($filtered, $filters['tienda_salud'] ?? '');

            $rowsQuery = $this->addTiendaSaludFlag((clone $filtered)->select($columns));
            $this->aplicarOrdenTabla($rowsQuery, $sort, $columns);

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
            $conn = $this->conexion();
            $base = $conn->table('tiendas');
            $this->aplicarPeriodoActivo($base, $regionFilters);
            $this->aplicarFiltroRegional($base, $regionFilters);
            $usarDerivados = $this->derivadosCompletos($regionFilters);

            $filtered = clone $base;
            $this->aplicarFiltrosCriticidad($filtered, $filters, $usarDerivados);
            $this->aplicarFiltroTiendaSalud($filtered, $filters['tienda_salud'] ?? '');

            $selectColumns = array_values(array_unique(array_merge($columns, ['nivel_critico', 'factores_criticos_count'])));
            $rowsQuery = $this->addTiendaSaludFlag((clone $filtered)->select($selectColumns));
            $this->aplicarOrdenCriticidad($rowsQuery, $sort, $columns, $usarDerivados);

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
            $conn = $this->conexion();
            $base = $conn->table('tiendas');
            $this->aplicarPeriodoActivo($base, $regionFilters);
            $this->aplicarFiltroRegional($base, $regionFilters);
            $usarDerivados = $this->derivadosCompletos($regionFilters);

            $filtered = clone $base;
            $this->aplicarFiltrosAuditoria($filtered, $filters, $usarDerivados);
            $this->aplicarFiltroTiendaSalud($filtered, $filters['tienda_salud'] ?? '');

            $selectColumns = array_values(array_unique(array_merge($columns, ['nivel_critico', 'estado_comite', 'rango_rotacion', 'auditoria_pendiente'])));
            $rowsQuery = $this->addTiendaSaludFlag((clone $filtered)->select($selectColumns));
            $this->aplicarOrdenAuditoria($rowsQuery, $sort, $columns, $usarDerivados);

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
            $conn = $this->conexion();
            $base = $conn->table('tiendas');
            $this->aplicarPeriodoActivo($base, $regionFilters);
            $this->aplicarFiltroRegional($base, $regionFilters);

            $filtered = clone $base;
            $this->aplicarFiltrosAperturas($filtered, $filters);
            $this->aplicarFiltroTiendaSalud($filtered, $filters['tienda_salud'] ?? '');

            $rowsQuery = $this->addTiendaSaludFlag((clone $filtered)->select($columns));
            $this->aplicarOrdenAperturas($rowsQuery, $sort, $columns);

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
        $query = $this->conexion()->table('tiendas');
        $this->aplicarPeriodoActivo($query, $regionFilters);
        $this->aplicarFiltroRegional($query, $regionFilters);
        $this->aplicarFiltrosMapa($query, $filters);
        $this->aplicarFiltroTiendaSalud($query, $filters['tienda_salud'] ?? '');

        $rows = $this->selectMapaColumns($query, $columns, $filters['tienda_salud'] ?? null)
            ->orderBy('id')
            ->limit(20000)
            ->get()
            ->map(fn ($row) => $this->presentador->rowToGeoStore($row, $columns))
            ->all();

        return $rows;
    }

    public function obtenerMapaViewport(array $regionFilters, array $filters, array $bounds, array $columns, int $limit = 3000): array
    {
        $query = $this->conexion()->table('tiendas');
        $this->aplicarPeriodoActivo($query, $regionFilters);
        $this->aplicarFiltroRegional($query, $regionFilters);
        $this->aplicarFiltrosMapa($query, $filters);
        $this->aplicarFiltroTiendaSalud($query, $filters['tienda_salud'] ?? '');
        if (! in_array($filters['estado_geo'] ?? '', ['FUERA_MEXICO', 'INCIDENCIAS'], true)) {
            $this->aplicarBounds($query, $bounds, 'Latitud', 'Longitud');
        }

        $rows = $this->selectMapaColumns($query, $columns, $filters['tienda_salud'] ?? null)
            ->whereNotNull('Latitud')
            ->whereNotNull('Longitud')
            ->where('Latitud', '!=', '0')
            ->where('Longitud', '!=', '0')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => $this->presentador->rowToGeoStore($row, $columns))
            ->all();

        return $rows;
    }

    public function contarMapaFiltrado(array $regionFilters, array $filters): int
    {
        $query = $this->conexion()->table('tiendas');
        $this->aplicarPeriodoActivo($query, $regionFilters);
        $this->aplicarFiltroRegional($query, $regionFilters);
        $this->aplicarFiltrosMapa($query, $filters);
        $this->aplicarFiltroTiendaSalud($query, $filters['tienda_salud'] ?? '');

        return $query->count();
    }

    public function obtenerIncidenciasMapaPaginadas(array $regionFilters, array $filters, array $columns, ?string $sort = null, string $direction = 'asc', int $page = 1, int $perPage = 50): array
    {
        $filters['sort'] = $sort;
        $filters['direction'] = $direction;

        $countQuery = $this->conexion()->table('tiendas');
        $this->aplicarPeriodoActivo($countQuery, $regionFilters);
        $this->aplicarFiltroRegional($countQuery, $regionFilters);
        $this->aplicarFiltrosMapa($countQuery, $filters);
        $this->aplicarFiltroTiendaSalud($countQuery, $filters['tienda_salud'] ?? '');
        $countQuery->whereNotIn('estado_geo', ['OK']);
        $total = $countQuery->count();

        $dataQuery = $this->conexion()->table('tiendas');
        $this->aplicarPeriodoActivo($dataQuery, $regionFilters);
        $this->aplicarFiltroRegional($dataQuery, $regionFilters);
        $this->aplicarFiltrosMapa($dataQuery, $filters);
        $this->aplicarFiltroTiendaSalud($dataQuery, $filters['tienda_salud'] ?? '');
        $dataQuery->whereNotIn('estado_geo', ['OK']);

        $sortable = ['Nombre_Almacen', 'No_Tienda_Actual', 'Municipio', 'Estado'];
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        if ($sort && in_array($sort, $sortable, true)) {
            $dataQuery->orderBy($sort, $direction);
        } else {
            $dataQuery->orderBy('id');
        }

        $offset = max(0, ($page - 1) * $perPage);
        $rows = $this->selectMapaColumns($dataQuery, $columns, $filters['tienda_salud'] ?? null)
            ->limit($perPage)
            ->offset($offset)
            ->get()
            ->map(fn ($row) => $this->presentador->rowToGeoStore($row, $columns))
            ->all();

        return ['items' => $rows, 'total' => $total];
    }

    public function obtenerDashboardMetricas(array $regionFilters): array
    {
        $base = $this->conexion()->table('tiendas');
        $this->aplicarPeriodoActivo($base, $regionFilters);
        $this->aplicarFiltroRegional($base, $regionFilters);

        $total = (clone $base)->count();
        $usarDerivados = $this->derivadosCompletos($regionFilters);
        $aperturasPorMes = $this->kpiTiendas->aperturasPorMes(clone $base);
        $directorioStats = $this->kpiTiendas->statsDirectorio(clone $base, $this->trackedDirectorioColumns);
        $completos = max(0, $total - ($directorioStats['incompletos'] ?? 0));

        return [
            'totalCount' => $total,
            'connectivityKpis' => $this->kpiTiendas->kpisConectividad(clone $base),
            'criticalSummary' => $this->kpiTiendas->resumenCriticidad(clone $base, $usarDerivados),
            'sinConectividad' => $this->kpiTiendas->sinConectividadCount(clone $base),
            'aperturasEsteMes' => $this->kpiTiendas->aperturasEsteMesCount(clone $base),
            'geoStats' => $this->kpiTiendas->geoStats(clone $base, $usarDerivados),
            'aperturasKpi' => $this->kpiTiendas->aperturasKpiDashboard(clone $base),
            'aperturasPorMes' => $aperturasPorMes,
            'directorioStats' => ['completos' => $completos, 'incompletos' => (int) ($directorioStats['incompletos'] ?? 0)],
            'auditoriaKpis' => $this->kpiTiendas->kpisAuditoria(clone $base, $usarDerivados),
        ];
    }

    public function exportarTiendas(array $regionFilters, array $filters, array $columns, string $module): \Generator
    {
        $query = $this->conexion()->table('tiendas');
        $this->aplicarPeriodoActivo($query, $regionFilters);
        $this->aplicarFiltroRegional($query, $regionFilters);

        if ($module === 'conectividad') {
            $this->aplicarFiltrosConectividad($query, $filters);
        } elseif ($module === 'directorio') {
            $this->aplicarFiltrosDirectorio($query, $filters, $this->trackedDirectorioColumns);
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

        $this->aplicarFiltroTiendaSalud($query, $filters['tienda_salud'] ?? '');

        foreach ($this->addTiendaSaludFlag($query->select($columns), $filters['tienda_salud'] ?? null)->orderBy('id')->cursor() as $row) {
            yield match ($module) {
                'criticidad' => $this->presentador->rowToCriticalStore($row, $columns),
                'auditoria' => $this->presentador->rowToAuditStore($row, $columns),
                'aperturas' => $this->presentador->rowToAperturaStore($row, $columns),
                'mapa' => $this->presentador->rowToGeoStore($row, $columns),
                default => $this->presentador->rowToStore($row, $columns),
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

    public function diagnosticoDerivados(array $regionFilters = []): array
    {
        try {
            $query = $this->conexion()->table('tiendas');
            $this->aplicarPeriodoActivo($query, $regionFilters);
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

    private function aplicarPeriodoActivo(Builder $query, array $filters = []): void
    {
        if (! empty($filters['periodo_importacion_id'])) {
            $query->where('periodo_importacion_id', $filters['periodo_importacion_id']);
        } else {
            $query->where('es_activo', true);
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
            $query->whereRaw($this->indicadores->camposIncompletosSql($trackedColumns));
        }

        if (! empty($filters['sinCapital'])) {
            $query->whereRaw($this->indicadores->sinCapitalSql());
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

    private function aplicarFiltrosAuditoria(Builder $query, array $filters, bool $usarDerivados = false): void
    {
        if (($filters['almacen'] ?? '') !== '') {
            $query->whereRaw('"Nombre_Almacen" ILIKE ?', ['%'.$filters['almacen'].'%']);
        }

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

    private function aplicarOrdenCriticidad(Builder $query, array $sort, array $columns, bool $usarDerivados): void
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

    private function aplicarOrdenAuditoria(Builder $query, array $sort, array $columns, bool $usarDerivados): void
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

    private function selectMapaColumns(Builder $query, array $columns, ?string $tiendaSaludFilter = null): Builder
    {
        return $this->addTiendaSaludFlag(
            $query->select(array_values(array_unique(array_merge($columns, ['estado_geo'])))),
            $tiendaSaludFilter,
        );
    }

    private function addTiendaSaludFlag(Builder $query, ?string $tiendaSaludFilter = null): Builder
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

    private function aplicarFiltroTiendaSalud(Builder $query, string $filter): void
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

    private function conexion(): Connection
    {
        return DB::connection(config('database.imports'));
    }

    private function reverseMap(): array
    {
        $map = [];
        foreach (config('importacion.column_mapping', []) as $dbCol => $csvCol) {
            $map[$csvCol] = $dbCol;
        }

        return $map;
    }
}
