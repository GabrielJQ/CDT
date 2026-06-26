<?php

namespace App\Console\Commands;

use App\Jobs\FinalizarImportacionJob;
use App\Jobs\ProcesarChunkCsvJob;
use App\Servicios\ServicioMapeoColumnas;
use App\Servicios\ServicioPeriodosImportacion;
use App\Servicios\ServicioSanitizadorCsv;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class ImportCsv extends Command
{
    protected $signature = 'csv:import
        {file : Ruta relativa al archivo en storage/app/imports/}
        {--chunk=100000 : Filas por chunk}
        {--delimiter=, : Delimitador del CSV}
        {--anio= : Año del periodo}
        {--trimestre= : Trimestre T1, T2, T3 o T4}
        {--fecha-corte= : Fecha de corte del periodo}
        {--reemplazar : Reemplaza el periodo si ya existe}
        {--dry-run : Solo validar, no importar}
    ';

    protected $description = 'Carga masiva de CSV a Supabase vía COPY';

    public function handle(ServicioSanitizadorCsv $sanitizer, ServicioPeriodosImportacion $periodos): int
    {
        $originalPath = storage_path('app/imports/'.$this->argument('file'));
        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');
        $delimiter = $this->option('delimiter');

        if (! file_exists($originalPath)) {
            $this->error("Archivo no encontrado: {$originalPath}");

            return self::FAILURE;
        }

        $sanitizedDir = storage_path('app/imports/_sanitized');
        $chunkDir = storage_path('app/imports/_chunks');
        @mkdir($sanitizedDir, 0755, true);

        $sanitizedPath = "{$sanitizedDir}/".basename($this->argument('file'));

        // 1. Sanitizar
        $this->info('Sanitizando CSV...');
        $stats = $sanitizer->sanitizar($originalPath, $sanitizedPath, $delimiter);
        $this->line("  {$stats['total_lines']} líneas, {$stats['encoding_fixes']} fixes de encoding, {$stats['control_chars_removed']} chars de control");

        // 2. Validar header vs mapeo
        $this->info('Validando columnas...');
        $header = $sanitizer->extraerHeader($sanitizedPath, $delimiter);
        $mapper = ServicioMapeoColumnas::make();
        $advertencias = $mapper->validarColumnas($header);

        if (! empty($advertencias)) {
            $this->warn('Columnas del CSV sin mapeo en configuración:');
            foreach (array_values($advertencias) as $w) {
                $this->warn("  - {$w}");
            }
        } else {
            $this->info('  Todas las columnas del mapeo existen en el CSV.');
        }

        if ($dryRun) {
            $this->info('Dry-run: validación completada. No se importó nada.');

            return self::SUCCESS;
        }

        $periodo = null;
        if ($this->option('anio') !== null || $this->option('trimestre') !== null) {
            if ($this->option('anio') === null || $this->option('trimestre') === null) {
                $this->error('Debes enviar --anio y --trimestre juntos.');

                return self::FAILURE;
            }

            try {
                $periodo = $periodos->preparar(
                    ServicioPeriodosImportacion::TIPO_REGULAR,
                    (int) $this->option('anio'),
                    $this->option('trimestre'),
                    $this->option('fecha-corte'),
                    basename($this->argument('file')),
                    (bool) $this->option('reemplazar'),
                );
            } catch (\Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
        }

        // 3. Contar filas
        $totalRows = $sanitizer->contarFilas($sanitizedPath);
        $totalChunks = (int) ceil($totalRows / $chunkSize);
        $this->info("{$totalRows} filas de datos en ~{$totalChunks} chunks de {$chunkSize}");

        // 4. Dividir en chunks
        $this->info('Dividiendo en chunks...');
        $chunkFiles = $sanitizer->dividirEnChunks($sanitizedPath, $chunkDir, $chunkSize, $delimiter);
        $this->line('  '.count($chunkFiles).' archivos chunk creados');

        // 5. Dispatch batch
        $filename = basename($this->argument('file'));
        $this->info('Despachando '.count($chunkFiles).' jobs...');

        $jobs = [];
        foreach ($chunkFiles as $index => $chunkPath) {
            $jobs[] = new ProcesarChunkCsvJob(
                chunkPath: $chunkPath,
                chunkIndex: $index,
                delimiter: $delimiter,
                periodoImportacionId: $periodo ? (int) $periodo->id : null,
            );
        }

        $batch = Bus::batch($jobs)
            ->name("Importación CSV: {$filename}")
            ->allowFailures()
            ->onQueue('imports')
            ->then(function () use ($periodo) {
                FinalizarImportacionJob::dispatch($periodo ? (int) $periodo->id : null)->onQueue('imports');
            })
            ->finally(function () use ($filename) {
                Log::info("Batch de importación '{$filename}' completado");
            })
            ->dispatch();

        $this->info("Batch ID: {$batch->id}");
        $this->line('Ejecuta: php artisan queue:work --queue=imports --tries=3 --timeout=600 --backoff=30');

        return self::SUCCESS;
    }
}
