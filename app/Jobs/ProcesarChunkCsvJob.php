<?php

namespace App\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
    ) {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        if (! file_exists($this->chunkPath)) {
            Log::warning("Chunk {$this->chunkIndex} no encontrado, omitiendo.", ['path' => $this->chunkPath]);

            return;
        }

        $this->asegurarStagingTable();

        $pdo = $this->conexionDirecta();

        try {
            $pdo->exec("SET statement_timeout = '300000'");
            $pdo->exec('SET synchronous_commit = OFF');

            $this->copiarViaStdin($pdo);

            $count = DB::connection('pgsql_imports')
                ->table('staging_import')
                ->whereNull('_chunk_index')
                ->update(['_chunk_index' => $this->chunkIndex]);

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
        $failedDir = dirname($this->chunkPath).DIRECTORY_SEPARATOR.'_failed';
        @mkdir($failedDir, 0755, true);

        $destino = "{$failedDir}/chunk_{$this->chunkIndex}.csv";
        rename($this->chunkPath, $destino);

        Log::error("Chunk {$this->chunkIndex} definitivamente falló", [
            'error' => $e->getMessage(),
            'archivo' => $destino,
        ]);
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

    private function asegurarStagingTable(): void
    {
        $conn = DB::connection('pgsql_imports');

        if (Schema::connection('pgsql_imports')->hasTable('staging_import')) {
            return;
        }

        $header = $this->leerHeader();
        if (empty($header)) {
            throw new \RuntimeException("No se pudo leer el header del chunk {$this->chunkIndex}");
        }

        Schema::connection('pgsql_imports')->create('staging_import', function (Blueprint $table) use ($header) {
            $table->id();

            foreach ($header as $col) {
                $colClean = $this->sanitizarNombreColumna($col);
                $table->text($colClean)->nullable();
            }

            $table->unsignedInteger('_chunk_index')->nullable();
            $table->string('_status', 20)->default('staged');
            $table->json('_errors')->nullable();
            $table->timestamps();
        });

        Log::info('Tabla staging_import creada con '.count($header).' columnas');
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

    private function copiarViaStdin(PDO $pdo): void
    {
        $header = $this->leerHeader();
        $columnasSanitizadas = array_map(fn ($c) => '"'.$this->sanitizarNombreColumna($c).'"', $header);
        $fields = implode(', ', $columnasSanitizadas);

        $dataOnly = $this->chunkPath.'.dataonly';
        $in = fopen($this->chunkPath, 'r');
        $out = fopen($dataOnly, 'w');

        fgetcsv($in, 0, $this->delimiter);
        $numColumns = count($header);

        while (($row = fgetcsv($in, 0, $this->delimiter)) !== false) {
            if (count($row) === 1 && $row[0] === null) {
                continue;
            }

            $normalized = array_pad($row, $numColumns, '');
            $normalized = array_map(fn ($v) => $v ?? '', $normalized);
            fputcsv($out, $normalized, $this->delimiter);
        }

        fclose($in);
        fclose($out);

        $this->pgCopyCsv('staging_import', $dataOnly, $fields);

        unlink($dataOnly);
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
