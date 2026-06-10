<?php

namespace App\Console\Commands;

use App\Servicios\ServicioMapeoColumnas;
use App\Servicios\ServicioSanitizadorCsv;
use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestCopyCsv extends Command
{
    protected $signature = 'csv:test-copy
        {file : Ruta relativa en storage/app/imports/}
        {--rows=10 : Filas a copiar}
        {--delimiter=, : Delimitador}
    ';

    protected $description = 'Prueba el pipeline COPY de un CSV fragmento a Supabase';

    public function handle(): int
    {
        $path = storage_path('app/imports/'.$this->argument('file'));
        $rows = (int) $this->option('rows');
        $delimiter = $this->option('delimiter');

        if (! file_exists($path)) {
            $this->error("Archivo no encontrado: {$path}");

            return self::FAILURE;
        }

        $fragment = dirname($path).'/_test_fragment.csv';

        $this->line('1. Sanitizando...');
        $sanitizer = app(ServicioSanitizadorCsv::class);
        $sanitizedPath = dirname($path).'/_test_sanitized.csv';
        $sanitizer->sanitizar($path, $sanitizedPath, $delimiter);

        $this->line('2. Extrayendo '.$rows.' filas...');
        $in = fopen($sanitizedPath, 'r');
        $out = fopen($fragment, 'w');

        $header = fgetcsv($in, 0, $delimiter);
        if ($header === false) {
            $this->error('No se pudo leer el header');
            fclose($in);
            fclose($out);
            @unlink($fragment);
            @unlink($sanitizedPath);

            return self::FAILURE;
        }
        fputcsv($out, $header, $delimiter);

        $count = 0;
        while (($row = fgetcsv($in, 0, $delimiter)) !== false && $count < $rows) {
            if (count($row) === 1 && $row[0] === null) {
                continue;
            }
            $normalized = array_pad($row, count($header), '');
            $normalized = array_map(fn ($v) => $v ?? '', $normalized);
            fputcsv($out, $normalized, $delimiter);
            $count++;
        }

        fclose($in);
        fclose($out);
        $this->line("   {$count} filas escritas en fragmento");

        $this->line('3. Creando tabla staging_import...');
        Schema::connection('pgsql_imports')->dropIfExists('staging_import');
        Schema::connection('pgsql_imports')->create('staging_import', function (Blueprint $table) use ($header) {
            $table->id();
            foreach ($header as $col) {
                $table->text(preg_replace('/[^a-zA-Z0-9_ áéíóúñÁÉÍÓÚÑàèìòùÀÈÌÒÙäëïöüÄËÏÖÜâêîôûÂÊÎÔÛ,\-\(\)\/\']/', '_', $col))->nullable();
            }
            $table->unsignedInteger('_chunk_index')->nullable();
            $table->string('_status', 20)->default('staged');
            $table->json('_errors')->nullable();
            $table->timestamps();
        });
        $this->line('   Tabla creada con '.count($header).' columnas');

        $this->line('4. Creando archivo data-only (sin header)...');
        $dataOnly = $fragment.'.dataonly';
        $in = fopen($fragment, 'r');
        $out = fopen($dataOnly, 'w');

        fgetcsv($in, 0, $delimiter);
        $numColumns = count($header);

        $dataCount = 0;
        while (($row = fgetcsv($in, 0, $delimiter)) !== false) {
            if (count($row) === 1 && $row[0] === null) {
                continue;
            }
            $normalized = array_pad($row, $numColumns, '');
            $normalized = array_map(fn ($v) => $v ?? '', $normalized);
            fputcsv($out, $normalized, $delimiter);
            $dataCount++;
        }
        fclose($in);
        fclose($out);
        $this->line("   {$dataCount} filas en data-only (sin nulls ni padding)");

        $sanitizedColumns = array_map(fn ($c) => '"'.preg_replace('/[^a-zA-Z0-9_ áéíóúñÁÉÍÓÚÑàèìòùÀÈÌÒÙäëïöüÄËÏÖÜâêîôûÂÊÎÔÛ,\-\(\)\/\']/', '_', $c).'"', $header);
        $fields = implode(', ', $sanitizedColumns);

        $this->line('5. Ejecutando pg_put_line con FORMAT CSV...');
        try {
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
            $sql = "COPY staging_import ({$fields}) FROM STDIN WITH (FORMAT CSV, DELIMITER ',', NULL '')";
            pg_query($pg, $sql);

            $handle = fopen($dataOnly, 'r');
            if (! $handle) {
                throw new \RuntimeException("No se pudo abrir {$dataOnly}");
            }
            while (($line = fgets($handle)) !== false) {
                pg_put_line($pg, $line);
            }
            fclose($handle);
            pg_end_copy($pg);
            pg_close($pg);
        } catch (\Throwable $e) {
            $this->error('   COPY falló: '.$e->getMessage());
            $this->cleanup($fragment, $sanitizedPath, $dataOnly);

            return self::FAILURE;
        }

        $stagingCount = DB::connection('pgsql_imports')->table('staging_import')->count();
        $this->line("7. Filas en staging_import: {$stagingCount}");

        $this->line('8. Probando mapeo a tiendas...');
        $mapper = ServicioMapeoColumnas::make();
        $errores = 0;
        DB::connection('pgsql_imports')->table('staging_import')
            ->where('_status', 'staged')
            ->chunk(100, function ($filas) use ($mapper, &$errores) {
                foreach ($filas as $fila) {
                    try {
                        $data = $mapper->mapear($fila);
                    } catch (\Throwable $e) {
                        $errores++;
                    }
                }
            });

        if ($errores > 0) {
            $this->warn("   {$errores} filas con error de mapeo");
        } else {
            $this->line('   Todas las filas mapean correctamente');
        }

        $this->info('Test completado exitosamente.');
        $this->cleanup($fragment, $sanitizedPath, $dataOnly);

        return self::SUCCESS;
    }

    private function cleanup(string ...$paths): void
    {
        foreach ($paths as $p) {
            @unlink($p);
        }
    }
}
