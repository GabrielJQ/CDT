<?php

namespace App\Jobs;

use App\Servicios\ServicioDerivadosTienda;
use App\Servicios\ServicioJerarquiaOperativa;
use App\Servicios\ServicioMapeoColumnas;
use App\Servicios\ServicioPeriodosImportacion;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FinalizarImportacionJob implements ShouldQueue
{
    use Batchable, Queueable;

    public $timeout = 1200;

    public $tries = 1;

    private array $columnasFecha = [
        'Fecha_Apertura', 'Fecha_Autoriza', 'Fecha_Autoriza_Consejo', 'Enc_FecNac',
        'Pagare_Fecha', 'Fecha_Pos', 'Fech_Mov_Reap', 'Fch_Audit', 'Asam_Fch_',
        'Fec_CRA', 'Vigencia',
    ];

    public function __construct(
        public ?int $periodoImportacionId = null,
    ) {
        $this->onQueue('imports');
    }

    public function handle(
        ServicioDerivadosTienda $derivados,
        ServicioPeriodosImportacion $periodos,
        ServicioJerarquiaOperativa $jerarquia,
        ServicioMapeoColumnas $mapper,
    ): void {
        $conn = DB::connection(config('database.imports'));

        $stagingQuery = $conn->table('staging_import')->where('_status', 'staged');

        if ($this->periodoImportacionId !== null) {
            $stagingQuery->where('periodo_importacion_id', $this->periodoImportacionId);
        }

        $total = (clone $stagingQuery)->count();
        if ($total === 0) {
            Log::info('No hay filas pendientes en staging_import');

            return;
        }

        $limites = $this->cargarLimitesColumnas($conn);
        $exitos = 0;
        $errores = 0;

        $stagingQuery->chunkById(300, function ($filas) use ($conn, $mapper, $limites, $derivados, $periodos, &$exitos, &$errores) {
            $batch = [];
            $idsOk = [];
            $idsError = [];
            $errors = [];

            foreach ($filas as $fila) {
                try {
                    $data = $this->convertirFechas($mapper->mapear($fila));
                    $data = $derivados->agregar($data);
                    if ($this->periodoImportacionId !== null) {
                        $data['periodo_importacion_id'] = $this->periodoImportacionId;
                        $data['es_activo'] = false;
                        $data['llave_tienda_periodo'] = $periodos->llaveRegular($data);
                    }
                    $data = $this->truncarValores($data, $limites);
                    $batch[] = $data;
                    $idsOk[] = $fila->id;
                    $exitos++;
                } catch (\Throwable $e) {
                    $idsError[] = $fila->id;
                    $errors[$fila->id] = $e->getMessage();
                    $errores++;
                }
            }

            if (! empty($batch)) {
                try {
                    $conn->table('tiendas')->insert($batch);

                    $conn->table('staging_import')
                        ->whereIn('id', $idsOk)
                        ->update(['_status' => 'valid']);
                } catch (\Throwable $batchError) {
                    foreach ($batch as $i => $row) {
                        try {
                            $conn->table('tiendas')->insert($row);
                            $conn->table('staging_import')
                                ->where('id', $idsOk[$i])
                                ->update(['_status' => 'valid']);
                        } catch (\Throwable $rowError) {
                            $id = $idsOk[$i];
                            $conn->table('staging_import')
                                ->where('id', $id)
                                ->update([
                                    '_status' => 'error',
                                    '_errors' => json_encode([$rowError->getMessage()]),
                                ]);
                            $errores++;
                            $exitos--;
                        }
                    }
                }
            }

            if (! empty($idsError)) {
                foreach ($idsError as $id) {
                    $conn->table('staging_import')
                        ->where('id', $id)
                        ->update([
                            '_status' => 'error',
                            '_errors' => json_encode([$errors[$id]]),
                        ]);
                }
            }
        });

        Log::info('Importación finalizada', [
            'total' => $total,
            'exitos' => $exitos,
            'errores' => $errores,
        ]);

        if ($errores > 0) {
            $this->exportarErrores($conn);
        }

        if ($this->periodoImportacionId !== null) {
            $periodos->rellenarCamposRegional(ServicioPeriodosImportacion::TIPO_REGULAR, $this->periodoImportacionId);
            $periodos->activar(ServicioPeriodosImportacion::TIPO_REGULAR, $this->periodoImportacionId, $exitos, $errores);
        }

        $jerarquia->sincronizar();

        Cache::flush();
    }

    public function failed(\Throwable $e): void
    {
        Log::error('FinalizarImportacionJob falló críticamente', [
            'error' => $e->getMessage(),
            'batch_id' => $this->batchId,
        ]);
    }

    private function cargarLimitesColumnas($conn): array
    {
        $columnas = $conn->select("
            SELECT column_name, character_maximum_length
            FROM information_schema.columns
            WHERE table_name = 'tiendas'
              AND data_type = 'character varying'
              AND character_maximum_length IS NOT NULL
        ");

        $limites = [];
        foreach ($columnas as $col) {
            $limites[$col->column_name] = (int) $col->character_maximum_length;
        }

        return $limites;
    }

    private function truncarValores(array $data, array $limites): array
    {
        foreach ($data as $col => $valor) {
            if (is_string($valor) && isset($limites[$col])) {
                $maxLen = $limites[$col];
                if (mb_strlen($valor) > $maxLen) {
                    $data[$col] = mb_substr($valor, 0, $maxLen);
                }
            }
        }

        return $data;
    }

    private function convertirFechas(array $data): array
    {
        foreach ($this->columnasFecha as $col) {
            if (! isset($data[$col]) || ! is_string($data[$col]) || trim($data[$col]) === '') {
                $data[$col] = null;

                continue;
            }

            $val = trim($data[$col]);

            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $val)) {
                try {
                    $data[$col] = Carbon::createFromFormat('d/m/Y', $val)->format('Y-m-d');
                } catch (\Throwable) {
                    $data[$col] = null;
                }
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                try {
                    Carbon::parse($val);
                    $data[$col] = $val;
                } catch (\Throwable) {
                    $data[$col] = null;
                }
            } else {
                $data[$col] = null;
            }
        }

        return $data;
    }

    private function exportarErrores($conn): void
    {
        $errores = $conn->table('staging_import')
            ->where('_status', 'error')
            ->get();

        $relativeDir = 'imports/_reportes';
        Storage::disk('local')->makeDirectory($relativeDir);

        $filename = 'errores_'.now()->format('Ymd_His').'.csv';
        $path = $relativeDir.DIRECTORY_SEPARATOR.$filename;

        $fullPath = Storage::disk('local')->path($path);

        $out = fopen($fullPath, 'w');

        $first = $errores->first();
        if ($first) {
            $headers = array_keys(get_object_vars($first));
            fputcsv($out, $headers);

            foreach ($errores as $row) {
                fputcsv($out, (array) $row);
            }
        }

        fclose($out);

        Log::info("Reporte de errores generado: {$fullPath}");
    }
}
