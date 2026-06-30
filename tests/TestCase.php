<?php

namespace Tests;

use App\Models\User;
use App\Servicios\ServicioPostgresql;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected static ?string $tempMigrationDir = null;

    protected ?User $user = null;

    protected function signIn(?User $user = null): static
    {
        $this->user = $user ?? User::factory()->create();

        return $this->actingAs($this->user);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Use pgsql_imports as default so all tables (including those from
        // migrations with Schema::connection('pgsql_imports')) live in the
        // same database — FK constraints work across connections in tests.
        config([
            'database.default' => 'pgsql_imports',
            'database.connections.pgsql_imports' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        if (static::$tempMigrationDir === null) {
            static::$tempMigrationDir = sys_get_temp_dir().'/cdt_migrations_'.uniqid();
            mkdir(static::$tempMigrationDir, 0777, true);

            foreach ([
                '0001_01_01_000000_create_users_table.php',
                '2026_06_24_145033_create_regions_table.php',
                '2026_06_24_145036_create_unidad_operativas_table.php',
                '2026_06_24_145055_add_access_control_fields_to_users_table.php',
                '2026_06_24_160953_fix_unidad_operativas_unique_key.php',
            ] as $file) {
                copy(database_path('migrations/'.$file), static::$tempMigrationDir.'/'.$file);
            }
        }

        $this->artisan('migrate', [
            '--database' => config('database.default'),
            '--path' => static::$tempMigrationDir,
            '--realpath' => true,
            '--no-interaction' => true,
        ]);

        DB::beginTransaction();

        $this->beforeApplicationDestroyed(function () {
            DB::rollBack();
        });

        $this->app->bind(ServicioPostgresql::class, function () {
            return new class extends ServicioPostgresql
            {
                public function __construct() {}

                public function tieneDatos(): bool
                {
                    return true;
                }

                public function getUltimoError(): ?string
                {
                    return null;
                }

                public function obtenerTiendas(array $filters = [], ?array $columns = null): array
                {
                    return $this->filterRows($this->rows($columns), $filters);
                }

                public function obtenerConectividadPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $sort = []): array
                {
                    $rows = array_values(array_filter($this->filterRows($this->rows(), $regionFilters), fn (array $row) => $this->matchText($row, $filters['almacen'] ?? '')));

                    return $this->paged($rows, $page, $perPage, ['kpis' => $this->kpisConectividad($rows), 'companias' => ['AT&T', 'Movistar', 'Ninguno', 'Telcel']]);
                }

                public function obtenerDirectorioPaginado(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $trackedColumns, array $sort = []): array
                {
                    $rows = $this->filterRows($this->rows($columns), $regionFilters);

                    return $this->paged($rows, $page, $perPage, ['stats' => ['incompletos' => 1, 'sinCapital' => 0]]);
                }

                public function obtenerCriticidadPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $sort = []): array
                {
                    $rows = array_map(fn (array $row) => $this->withCritical($row), $this->filterRows($this->rows($columns), $regionFilters));

                    return $this->paged($rows, $page, $perPage, ['summary' => ['rojo' => 1, 'amarillo' => 2, 'verde' => 3, 'desgloseLabels' => []]]);
                }

                public function obtenerAuditoriaPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $sort = []): array
                {
                    $rows = array_map(fn (array $row) => $this->withAudit($row), $this->filterRows($this->rows($columns), $regionFilters));

                    return $this->paged($rows, $page, $perPage, ['kpis' => [
                        'comitesVencidos' => 1, 'auditoriaAlta' => 1, 'rotacionBaja' => 1, 'auditoriaPendiente' => 1,
                        'rotacionCero' => 0, 'rotacionCritico' => 1, 'rotacionAmarillo' => 1, 'rotacionOptimo' => 1,
                        'auditoriasMes' => 1, 'sinAuditoriaTrimestre' => 1, 'sinAuditoriaAnio' => 1,
                    ]]);
                }

                public function obtenerAperturasPaginada(array $regionFilters, array $filters, int $page, int $perPage, array $columns, array $sort = []): array
                {
                    $rows = array_map(fn (array $row) => $this->withApertura($row), $this->filterRows($this->rows($columns), $regionFilters));

                    return $this->paged($rows, $page, $perPage, ['kpis' => ['total' => count($rows), 'esteMes' => 0, 'esteAnio' => 1, 'sinFecha' => 1]]);
                }

                public function obtenerMapa(array $regionFilters, array $filters, array $columns): array
                {
                    return array_map(fn (array $row) => $this->withGeo($row), $this->filterRows($this->rows($columns), $regionFilters));
                }

                public function obtenerMapaViewport(array $regionFilters, array $filters, array $bounds, array $columns, int $limit = 3000): array
                {
                    return array_slice($this->obtenerMapa($regionFilters, $filters, $columns), 0, $limit);
                }

                public function contarMapaFiltrado(array $regionFilters, array $filters): int
                {
                    return count($this->obtenerMapa($regionFilters, $filters, []));
                }

                public function obtenerIncidenciasMapaPaginadas(array $regionFilters, array $filters, array $columns, ?string $sort = null, string $direction = 'asc', int $page = 1, int $perPage = 50): array
                {
                    return ['items' => [], 'total' => 0];
                }

                public function obtenerDashboardMetricas(array $regionFilters): array
                {
                    $rows = $this->filterRows($this->rows(), $regionFilters);

                    return [
                        'totalCount' => count($rows),
                        'connectivityKpis' => $this->kpisConectividad($rows),
                        'criticalSummary' => ['rojo' => 1, 'amarillo' => 2, 'verde' => 3],
                        'sinConectividad' => 2,
                        'aperturasEsteMes' => 0,
                        'geoStats' => [
                            'OK' => 4,
                            'SIN_COORDENADAS' => 1,
                            'FUERA_MEXICO' => 1,
                            'FUERA_ESTADO' => 0,
                            'conCoordenadas' => 5,
                            'sinCoordenadas' => 1,
                            'incidencias' => 2,
                        ],
                        'aperturasKpi' => ['total' => 5, 'esteAnio' => 1],
                        'aperturasPorMes' => [['label' => 'Ene', 'count' => 1]],
                        'directorioStats' => ['completos' => 5, 'incompletos' => 1],
                        'auditoriaKpis' => [
                            'comitesVencidos' => 1, 'auditoriaAlta' => 1, 'rotacionBaja' => 1, 'auditoriaPendiente' => 1,
                            'rotacionCero' => 0, 'rotacionCritico' => 1, 'rotacionAmarillo' => 1, 'rotacionOptimo' => 1,
                            'auditoriasMes' => 1, 'sinAuditoriaTrimestre' => 1, 'sinAuditoriaAnio' => 1,
                        ],
                    ];
                }

                public function exportarTiendas(array $regionFilters, array $filters, array $columns, string $module): \Generator
                {
                    foreach ($this->filterRows($this->rows($columns), $regionFilters) as $row) {
                        yield match ($module) {
                            'criticidad' => $this->withCritical($row),
                            'auditoria' => $this->withAudit($row),
                            'aperturas' => $this->withApertura($row),
                            'mapa' => $this->withGeo($row),
                            default => $row,
                        };
                    }
                }

                private function rows(?array $columns = null): array
                {
                    $lines = file(__DIR__.'/fixtures/tiendas.csv', FILE_IGNORE_NEW_LINES);
                    $headers = str_getcsv($lines[6]);
                    $rows = [];
                    foreach (array_slice($lines, 7) as $line) {
                        $row = array_combine($headers, str_getcsv($line));
                        if ($columns !== null) {
                            $row = array_intersect_key($row, array_flip($columns));
                        }
                        $rows[] = $row;
                    }

                    return $rows;
                }

                private function filterRows(array $rows, array $filters): array
                {
                    return array_values(array_filter($rows, function (array $row) use ($filters) {
                        if (($filters['region'] ?? '') !== '' && ($row['Clave_Regional'] ?? '') !== $filters['region']) {
                            return false;
                        }
                        if (($filters['uo'] ?? '') !== '' && ($row['Clave_UniOpe'] ?? '') !== $filters['uo']) {
                            return false;
                        }

                        return true;
                    }));
                }

                private function paged(array $rows, int $page, int $perPage, array $extra): array
                {
                    return $extra + [
                        'rows' => array_slice($rows, ($page - 1) * $perPage, $perPage),
                        'total' => count($rows),
                        'filtered' => count($rows),
                    ];
                }

                private function withCritical(array $row): array
                {
                    $row['_critico'] = ['level' => 'rojo', 'count' => 4, 'conditions' => ['comite_vencido' => true], 'labels' => ['comite_vencido' => ['label' => 'Comité vencido', 'detail' => '']]];

                    return $row;
                }

                private function withAudit(array $row): array
                {
                    $row['_audit'] = ['level' => 'rojo', 'estadoComite' => 'vencido', 'fchAudit' => $row['Fch_Audit'] ?? '', 'mesesSinAuditoria' => 12, 'impuesto' => 650000, 'rotacion' => 0.2, 'rangoRotacion' => 'critico', 'auditRealizada' => 0, 'sinAuditoriaAnio' => true, 'auditoriaPendiente' => true, 'conditions' => ['auditoria_pendiente']];

                    return $row;
                }

                private function withApertura(array $row): array
                {
                    $fecha = $row['Fecha_Apertura'] ?? '';
                    $row['_fecha_apertura'] = $fecha !== '' ? '2024-01-01' : null;
                    $row['_antiguedad'] = '1 meses';

                    return $row;
                }

                private function withGeo(array $row): array
                {
                    $row['_geo'] = ['lat' => $row['Latitud'] ?? '', 'lon' => $row['Longitud'] ?? '', 'status' => 'OK', 'mensaje' => 'OK'];

                    return $row;
                }

                private function kpisConectividad(array $rows): array
                {
                    $total = count($rows);

                    return [
                        '_total' => $total,
                        'TELEFONIA' => ['label' => 'Teléfono', 'icon' => '📞', 'yes' => 1, 'no' => 1, 'undef' => 0, 'pctYes' => 50, 'pctNo' => 50],
                        'INTERNET' => ['label' => 'Internet', 'icon' => '🌐', 'yes' => 1, 'no' => 1, 'undef' => 0, 'pctYes' => 50, 'pctNo' => 50],
                        'Señal de celular' => ['label' => 'Señal Celular', 'icon' => '📱', 'yes' => 1, 'no' => 1, 'undef' => 0, 'pctYes' => 50, 'pctNo' => 50],
                        '_compania' => ['Telcel' => ['count' => 1, 'pct' => 50]],
                    ];
                }

                private function matchText(array $row, string $term): bool
                {
                    return $term === '' || str_contains(mb_strtoupper($row['Nombre_Almacen'] ?? ''), mb_strtoupper($term));
                }
            };
        });
    }
}
