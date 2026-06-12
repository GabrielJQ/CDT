<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportCasaPorCasa extends Command
{
    protected $signature = 'casa-x-casa:import
                            {file : Ruta absoluta al archivo XLSX}
                            {--truncate : Vaciar la tabla antes de importar}';

    protected $description = 'Importa el directorio Salud Casa por Casa desde Excel a la tabla tiendas_casa_x_casa';

    public function handle(): int
    {
        $path = $this->argument('file');

        if (! file_exists($path)) {
            $this->error("Archivo no encontrado: {$path}");

            return self::FAILURE;
        }

        $this->info('Leyendo archivo Excel...');

        try {
            $rows = $this->leerExcel($path);
        } catch (\Throwable $e) {
            $this->error("Error al leer el Excel: {$e->getMessage()}");

            return self::FAILURE;
        }

        if (empty($rows)) {
            $this->error('No se encontraron datos válidos en el archivo');

            return self::FAILURE;
        }

        $this->info('Total filas a importar: '.count($rows));

        $conn = DB::connection('pgsql_imports');

        if ($this->option('truncate')) {
            $conn->table('tiendas_casa_x_casa')->truncate();
            $this->info('Tabla truncada.');
        }

        $total = count($rows);
        $insertados = 0;
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach (array_chunk($rows, 500) as $chunk) {
            $conn->table('tiendas_casa_x_casa')->upsert(
                $chunk,
                ['no_tienda', 'almacen', 'estado', 'municipio'],
                [
                    'edo', 'mpio', 'loc', 'localidad', 'unidad_operativa',
                    'direccion', 'encargado', 'latitud', 'longitud',
                    'tipo_anaquel', 'estatus', 'anaqueles_instalados',
                    'aviso_funcionamiento', 'comentarios', 'updated_at',
                ]
            );
            $insertados += count($chunk);
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Importación completada: {$insertados} filas procesadas.");

        Log::info('Importacion CxC completada', ['total' => $insertados]);

        return self::SUCCESS;
    }

    private function leerExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $allData = $sheet->rangeToArray("A7:R{$sheet->getHighestRow()}", null, true, false);

        $rows = [];
        $siNo = fn ($v) => match (mb_strtoupper(trim((string) ($v ?? '')))) {
            'SÍ', 'SI' => true,
            default => false,
        };
        $now = now();

        foreach ($allData as $row) {
            $noTienda = trim((string) ($row[8] ?? ''));
            if ($noTienda === '') {
                continue;
            }

            $rows[] = [
                'edo' => $this->parseInt($row[0]),
                'estado' => trim((string) ($row[1] ?? '')),
                'mpio' => $this->parseInt($row[2]),
                'municipio' => trim((string) ($row[3] ?? '')),
                'loc' => $this->parseInt($row[4]),
                'localidad' => trim((string) ($row[5] ?? '')),
                'unidad_operativa' => trim((string) ($row[6] ?? '')),
                'almacen' => trim((string) ($row[7] ?? '')),
                'no_tienda' => $noTienda,
                'direccion' => $this->blankOrNull($row[9]),
                'encargado' => $this->blankOrNull($row[10]),
                'latitud' => $this->parseFloat($row[11]),
                'longitud' => $this->parseFloat($row[12]),
                'tipo_anaquel' => $this->blankOrNull($row[13]),
                'estatus' => $this->blankOrNull($row[14]),
                'anaqueles_instalados' => $siNo($row[15]),
                'aviso_funcionamiento' => $siNo($row[16]),
                'comentarios' => $this->blankOrNull($row[17]),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        return $rows;
    }

    private function parseInt(mixed $value): ?int
    {
        $v = trim((string) ($value ?? ''));
        if ($v === '' || $v === '0') {
            return null;
        }

        return (int) round((float) $v);
    }

    private function parseFloat(mixed $value): ?float
    {
        $v = trim((string) ($value ?? ''));
        if ($v === '' || $v === '0') {
            return null;
        }

        return (float) $v;
    }

    private function blankOrNull(mixed $value): ?string
    {
        $v = trim((string) ($value ?? ''));

        return $v !== '' ? $v : null;
    }
}
