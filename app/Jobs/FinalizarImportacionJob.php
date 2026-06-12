<?php

namespace App\Jobs;

use App\Http\Controllers\DashboardController;
use App\Servicios\ServicioAuditoria;
use App\Servicios\ServicioGeo;
use App\Servicios\ServicioMapeoColumnas;
use App\Servicios\ServicioTiendaCritica;
use Carbon\Carbon;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function __construct()
    {
        $this->onQueue('imports');
    }

    public function handle(): void
    {
        $mapper = ServicioMapeoColumnas::make();
        $auditoria = app(ServicioAuditoria::class);
        $critica = app(ServicioTiendaCritica::class);
        $geo = app(ServicioGeo::class);
        $conn = DB::connection('pgsql_imports');

        $total = $conn->table('staging_import')->where('_status', 'staged')->count();
        if ($total === 0) {
            Log::info('No hay filas pendientes en staging_import');

            return;
        }

        $limites = $this->cargarLimitesColumnas($conn);
        $exitos = 0;
        $errores = 0;

        $conn->table('staging_import')
            ->where('_status', 'staged')
            ->chunkById(300, function ($filas) use ($conn, $mapper, $limites, $auditoria, $critica, $geo, &$exitos, &$errores) {
                $batch = [];
                $idsOk = [];
                $idsError = [];
                $errors = [];

                foreach ($filas as $fila) {
                    try {
                        $data = $this->convertirFechas($mapper->mapear($fila));
                        $data = $this->agregarDerivados($data, $critica, $auditoria, $geo);
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

        DashboardController::invalidateDashboardCache();
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

    private function agregarDerivados(array $data, ServicioTiendaCritica $critica, ServicioAuditoria $auditoria, ServicioGeo $geo): array
    {
        $critico = $critica->evaluarTienda($data);
        $audit = $auditoria->evaluarTienda($data);
        $geoStatus = $geo->evaluarGeo($data);

        $data['nivel_critico'] = $critico['level'] ?? null;
        $data['factores_criticos_count'] = $critico['count'] ?? null;
        $data['estado_geo'] = $geoStatus['status'] ?? null;
        $data['estado_comite'] = $audit['estadoComite'] ?? null;
        $data['rango_rotacion'] = $audit['rangoRotacion'] ?? null;
        $data['auditoria_pendiente'] = $audit['auditoriaPendiente'] ?? null;

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

        $reportDir = storage_path('app/imports/_reportes');
        @mkdir($reportDir, 0755, true);

        $path = "{$reportDir}/errores_".now()->format('Ymd_His').'.csv';
        $out = fopen($path, 'w');

        $first = $errores->first();
        if ($first) {
            $headers = array_keys(get_object_vars($first));
            fputcsv($out, $headers);

            foreach ($errores as $row) {
                fputcsv($out, (array) $row);
            }
        }

        fclose($out);

        Log::info("Reporte de errores generado: {$path}");
    }
}
