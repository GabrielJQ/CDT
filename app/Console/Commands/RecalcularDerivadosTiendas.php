<?php

namespace App\Console\Commands;

use App\Http\Controllers\DashboardController;
use App\Servicios\ServicioDerivadosTienda;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalcularDerivadosTiendas extends Command
{
    protected $signature = 'tiendas:recalcular-derivados
        {--chunk=500 : Número de tiendas por lote}
        {--only= : Limita el recálculo a auditoria, criticidad, geo o fecha}
        {--dry-run : Calcula y diagnostica sin actualizar la base de datos}';

    protected $description = 'Recalcula columnas derivadas existentes de tiendas regulares';

    public function handle(ServicioDerivadosTienda $derivados): int
    {
        $only = $this->option('only') ?: null;
        if (! in_array($only, [null, 'auditoria', 'criticidad', 'geo', 'fecha'], true)) {
            $this->error('La opción --only debe ser auditoria, criticidad, geo o fecha.');

            return self::FAILURE;
        }

        $chunk = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');
        $conn = DB::connection('pgsql_imports');
        $total = $conn->table('tiendas')->count();

        if ($total === 0) {
            $this->warn('No hay tiendas para recalcular.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? 'Dry-run: ' : '')."Recalculando derivados para {$total} tiendas...");

        $procesadas = 0;
        $actualizadas = 0;
        $errores = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $conn->table('tiendas')
            ->orderBy('id')
            ->chunkById($chunk, function ($rows) use ($conn, $derivados, $only, $dryRun, &$procesadas, &$actualizadas, &$errores, $bar) {
                $updates = [];

                foreach ($rows as $row) {
                    try {
                        $derivadosCalculados = $derivados->calcular((array) $row, $only);
                        if ($derivadosCalculados !== []) {
                            $updates[] = ['id' => $row->id] + $derivadosCalculados;
                        }

                        $procesadas++;
                    } catch (\Throwable $e) {
                        $errores++;
                        $this->newLine();
                        $this->warn("Error en tienda id {$row->id}: {$e->getMessage()}");
                    }

                    $bar->advance();
                }

                if (! $dryRun && $updates !== []) {
                    try {
                        $conn->table('tiendas')->upsert(
                            $updates,
                            ['id'],
                            array_values(array_diff(array_keys($updates[0]), ['id'])),
                        );
                        $actualizadas += count($updates);
                    } catch (\Throwable $e) {
                        $errores += count($updates);
                        $this->newLine();
                        $this->warn("Error actualizando lote: {$e->getMessage()}");
                    }
                }
            });

        $bar->finish();
        $this->newLine(2);

        if (! $dryRun) {
            DashboardController::invalidateDashboardCache();
        }

        $this->info("Procesadas: {$procesadas}");
        $this->info('Actualizadas: '.($dryRun ? 0 : $actualizadas));
        $this->info("Errores: {$errores}");

        foreach ($this->diagnostico($conn) as $campo => $valor) {
            $this->line("{$campo}: {$valor}");
        }

        return $errores === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array<string, int>
     */
    private function diagnostico($conn): array
    {
        $row = $conn->table('tiendas')->selectRaw('
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
    }
}
