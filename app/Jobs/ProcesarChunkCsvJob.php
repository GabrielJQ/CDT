<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PDO;

class ProcesarChunkCsvJob implements ShouldQueue
{
    use Batchable, Queueable;

    public $timeout = 600;

    public $tries = 3;

    public $backoff = [30, 120, 300];

    public function __construct(
        public string $chunkPath,
        public int $chunkIndex,
        public string $delimiter = ',',
        public ?int $periodoImportacionId = null,
    ) {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        if (! file_exists($this->chunkPath)) {
            Log::warning("Chunk {$this->chunkIndex} no encontrado, omitiendo.", ['path' => $this->chunkPath]);

            return;
        }

        $header = $this->leerHeader();
        if (empty($header)) {
            throw new \RuntimeException("No se pudo leer el header del chunk {$this->chunkIndex}");
        }

        $columnasValidas = $this->filtrarColumnasVacias($header);
        if (empty($columnasValidas)) {
            throw new \RuntimeException("No hay columnas válidas en el chunk {$this->chunkIndex}");
        }

        $emptyCount = count($header) - count($columnasValidas);
        if ($emptyCount > 0) {
            Log::warning("Chunk {$this->chunkIndex}: {$emptyCount} columna(s) vacía(s) omitida(s)");
        }

        $this->asegurarStagingTable($columnasValidas);

        $pdo = $this->conexionDirecta();

        try {
            $pdo->exec("SET statement_timeout = '300000'");
            $pdo->exec('SET synchronous_commit = OFF');

            $this->copiarViaStdin($pdo, $columnasValidas);

            $stagingQuery = DB::connection('pgsql_imports')
                ->table('staging_import')
                ->whereNull('_chunk_index');

            $count = $stagingQuery->update(['_chunk_index' => $this->chunkIndex]);

            if ($this->periodoImportacionId !== null && $count > 0) {
                DB::connection('pgsql_imports')
                    ->table('staging_import')
                    ->where('_chunk_index', $this->chunkIndex)
                    ->update(['periodo_importacion_id' => $this->periodoImportacionId]);
            }

            Log::info("Chunk {$this->chunkIndex} importado: {$count} filas");
        } catch (\Throwable $e) {
            Log::error("Chunk {$this->chunkIndex} falló: {$e->getMessage()}");

            throw $e;
        } finally {
            unset($pdo);
        }
    }

    public function failed(\Throwable $e): void
    {
        $storagePrefix = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, storage_path('app'));
        $chunkNormalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->chunkPath);
        $relativePath = ltrim(str_replace($storagePrefix, '', $chunkNormalized), DIRECTORY_SEPARATOR);

        $failedDir = dirname($relativePath).DIRECTORY_SEPARATOR.'_failed';
        $failedPath = $failedDir.DIRECTORY_SEPARATOR.'chunk_'.$this->chunkIndex.'.csv';

        try {
            Storage::disk('local')->makeDirectory($failedDir);
            Storage::disk('local')->move($relativePath, $failedPath);

            $destino = Storage::disk('local')->path($failedPath);

            Log::error("Chunk {$this->chunkIndex} definitivamente falló", [
                'error' => $e->getMessage(),
                'archivo' => $destino,
            ]);
        } catch (\Throwable $moveErr) {
            Log::error("Chunk {$this->chunkIndex} falló y no se pudo mover: {$moveErr->getMessage()}", [
                'original_error' => $e->getMessage(),
                'chunk_path' => $this->chunkPath,
            ]);
        }
    }

    private function conexionDirecta(): PDO
    {
        return new PDO(
            "pgsql:host={$this->getDbHost()};port={$this->getDbPort()};dbname={$this->getDbName()}",
            $this->getDbUser(),
            $this->getDbPass(),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 600,
            ],
        );
    }

    private function asegurarStagingTable(array $columnasValidas): void
    {
        if (Schema::connection('pgsql_imports')->hasTable('staging_import')) {
            return;
        }

        Schema::connection('pgsql_imports')->create('staging_import', function (Blueprint $table) use ($columnasValidas) {
            $table->id();

            foreach ($columnasValidas as $col) {
                $colClean = $this->sanitizarNombreColumna($col);
                $table->text($colClean)->nullable();
            }

            $table->unsignedInteger('_chunk_index')->nullable();
            $table->unsignedBigInteger('periodo_importacion_id')->nullable();
            $table->string('_status', 20)->default('staged');
            $table->json('_errors')->nullable();
            $table->timestamps();
        });

        Log::info('Tabla staging_import creada con '.count($columnasValidas).' columnas');
    }

    private function sanitizarNombreColumna(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_ áéíóúñÁÉÍÓÚÑàèìòùÀÈÌÒÙäëïöüÄËÏÖÜâêîôûÂÊÎÔÛ,\-\(\)\/\']/', '_', $name);
    }

    private function leerHeader(): array
    {
        $file = new \SplFileObject($this->chunkPath, 'r');
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY);
        $file->setCsvControl($this->delimiter);

        $header = $file->fgetcsv();
        if ($header === false) {
            return [];
        }

        return array_map('trim', $header);
    }

    private function filtrarColumnasVacias(array $header): array
    {
        $resultado = [];
        foreach ($header as $i => $col) {
            if ($col !== '') {
                $resultado[$i] = $col;
            }
        }

        return $resultado;
    }

    private function copiarViaStdin(PDO $pdo, array $columnasValidas): void
    {
        $indicesValidos = array_keys($columnasValidas);
        $columnasSanitizadas = array_map(
            fn ($c) => '"'.$this->sanitizarNombreColumna($c).'"',
            $columnasValidas,
        );
        $fields = implode(', ', $columnasSanitizadas);

        $dataOnly = $this->chunkPath.'.dataonly';
        $in = fopen($this->chunkPath, 'r');
        $out = fopen($dataOnly, 'w');

        fgetcsv($in, 0, $this->delimiter);
        $numOutputColumns = count($columnasValidas);

        while (($row = fgetcsv($in, 0, $this->delimiter)) !== false) {
            if (count($row) === 1 && $row[0] === null) {
                continue;
            }

            $rowFiltrado = [];
            foreach ($indicesValidos as $idx) {
                $rowFiltrado[] = $row[$idx] ?? '';
            }

            $normalized = array_pad($rowFiltrado, $numOutputColumns, '');
            $normalized = array_map(fn ($v) => $v ?? '', $normalized);
            fputcsv($out, $normalized, $this->delimiter);
        }

        fclose($in);
        fclose($out);

        $this->pgCopyCsv('staging_import', $dataOnly, $fields);

        if (file_exists($dataOnly)) {
            unlink($dataOnly);
        }
    }

    private function pgCopyCsv(string $table, string $file, string $fields): void
    {
        $config = config('database.connections.pgsql_imports');
        $connStr = sprintf(
            "host='%s' port='%s' dbname='%s' user='%s' password='%s'",
            $config['host'],
            $config['port'],
            $config['database'],
            $config['username'],
            str_replace("'", "\\'", $config['password']),
        );

        $pg = pg_connect($connStr, PGSQL_CONNECT_FORCE_NEW);
        if (! $pg) {
            throw new \RuntimeException('pg_connect falló');
        }

        $sql = "COPY {$table} ({$fields}) FROM STDIN WITH (FORMAT CSV, DELIMITER ',', NULL '')";
        pg_query($pg, $sql);

        $handle = fopen($file, 'r');
        if (! $handle) {
            pg_close($pg);
            throw new \RuntimeException("No se pudo abrir {$file}");
        }

        while (($line = fgets($handle)) !== false) {
            pg_put_line($pg, $line);
        }

        fclose($handle);
        pg_end_copy($pg);
        pg_close($pg);
    }

    private function getDbHost(): string
    {
        return config('database.connections.pgsql_imports.host');
    }

    private function getDbPort(): string
    {
        return (string) config('database.connections.pgsql_imports.port', '5432');
    }

    private function getDbName(): string
    {
        return config('database.connections.pgsql_imports.database', 'postgres');
    }

    private function getDbUser(): string
    {
        return config('database.connections.pgsql_imports.username', 'postgres');
    }

    private function getDbPass(): string
    {
        return config('database.connections.pgsql_imports.password', '');
    }
}
