<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadCasaPorCasaRequest;
use App\Http\Requests\UploadImportRequest;
use App\Jobs\FinalizarImportacionJob;
use App\Jobs\ProcesarChunkCsvJob;
use App\Servicios\ServicioMapeoColumnas;
use App\Servicios\ServicioPeriodosImportacion;
use App\Servicios\ServicioSanitizadorCsv;
use App\Servicios\ServicioUpload;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    public function __construct(
        private ServicioUpload $upload,
    ) {}

    public function index()
    {
        $periodos = app(ServicioPeriodosImportacion::class);

        return view('imports', [
            'periodosActivos' => $periodos->activos(request()->user()),
            'trimestres' => $periodos->trimestres(),
            'currentYear' => (int) now()->year,
        ]);
    }

    public function upload(UploadImportRequest $request, ServicioSanitizadorCsv $sanitizer, ServicioPeriodosImportacion $periodos)
    {
        $anio = (int) $request->validated('anio');
        $trimestre = $request->validated('trimestre');
        $reemplazar = $request->boolean('reemplazar_periodo');

        if ($reemplazar && ! $request->user()->canImportGlobal()) {
            return back()->withInput($request->except('csv_file'))
                ->with('error', 'Solo usuarios con acceso nacional pueden reemplazar periodos.');
        }

        try {
            if ($periodos->existe(ServicioPeriodosImportacion::TIPO_REGULAR, $anio, $trimestre) && ! $reemplazar) {
                return back()
                    ->withInput($request->except('csv_file'))
                    ->with('error', $periodos->mensajeReemplazo(ServicioPeriodosImportacion::TIPO_REGULAR, $anio, $trimestre));
            }
        } catch (\Throwable $e) {
            return back()->withInput($request->except('csv_file'))->with('error', $e->getMessage());
        }

        $relativePath = $this->upload->storeImportFile($request->file('csv_file'));
        $destPath = $this->upload->fullPath($relativePath);
        $fileName = basename($destPath);

        $sanitizedPath = dirname($destPath).'/_sanitized/'.$fileName;
        $this->upload->ensureDirectory(dirname($sanitizedPath));

        $stats = $sanitizer->sanitizar($destPath, $sanitizedPath);

        $scopeType = 'global';
        $regionId = null;
        $unidadOperativaId = null;
        $user = $request->user();
        if ($user !== null && ! $user->hasGlobalAccess()) {
            $scopeType = $user->isRegional() ? 'regional' : 'unidad';
            $regionId = $user->region_id;
            if ($user->isUnidad()) {
                $unidadOperativaId = $user->unidad_operativa_id;
            }
        }

        try {
            $periodo = $periodos->preparar(
                ServicioPeriodosImportacion::TIPO_REGULAR,
                $anio,
                $trimestre,
                $request->validated('fecha_corte'),
                $fileName,
                $reemplazar,
                scopeType: $scopeType,
                regionId: $regionId,
                unidadOperativaId: $unidadOperativaId,
                uploadedBy: $user?->id,
            );
        } catch (\Throwable $e) {
            return back()->withInput($request->except('csv_file'))->with('error', $e->getMessage());
        }

        $header = $sanitizer->extraerHeader($sanitizedPath);
        $mapper = ServicioMapeoColumnas::make();
        $advertencias = $mapper->validarColumnas($header);

        $chunkDir = dirname($destPath).'/_chunks';
        $chunkFiles = $sanitizer->dividirEnChunks($sanitizedPath, $chunkDir, 100000);

        $jobs = [];
        foreach ($chunkFiles as $index => $chunkPath) {
            $jobs[] = new ProcesarChunkCsvJob(
                chunkPath: $chunkPath,
                chunkIndex: $index,
                periodoImportacionId: (int) $periodo->id,
            );
        }

        $batch = Bus::batch($jobs)
            ->name("Importación web: {$fileName}")
            ->allowFailures()
            ->onQueue('imports')
            ->then(function ($batch) use ($periodo) {
                if ($batch->hasFailures()) {
                    Log::warning('Importación cancelada por fallos en chunks', [
                        'periodo_id' => $periodo->id,
                        'failed_jobs' => $batch->failedJobs,
                    ]);

                    DB::connection('pgsql_imports')->table('periodos_importacion')
                        ->where('id', $periodo->id)
                        ->update(['estado' => 'error', 'updated_at' => now()]);

                    return;
                }

                FinalizarImportacionJob::dispatch((int) $periodo->id)->onQueue('imports');
            })
            ->dispatch();

        return redirect()->route('imports.index')->with('success', sprintf(
            'Archivo subido: %s (%d filas). Periodo: %s.',
            basename($destPath),
            $stats['total_lines'] - 1,
            $periodo->nombre,
        ));
    }

    public function uploadCasaPorCasa(UploadCasaPorCasaRequest $request, ServicioPeriodosImportacion $periodos)
    {
        $anio = (int) $request->validated('anio');
        $trimestre = $request->validated('trimestre');
        $reemplazar = $request->boolean('reemplazar_periodo');

        if ($reemplazar && ! $request->user()->canImportGlobal()) {
            return back()->withInput($request->except('xlsx_file'))
                ->with('error', 'Solo usuarios con acceso nacional pueden reemplazar periodos.');
        }

        if ($periodos->existe(ServicioPeriodosImportacion::TIPO_CASA_X_CASA, $anio, $trimestre) && ! $reemplazar) {
            return back()
                ->withInput($request->except('xlsx_file'))
                ->with('error', $periodos->mensajeReemplazo(ServicioPeriodosImportacion::TIPO_CASA_X_CASA, $anio, $trimestre));
        }

        $path = $this->upload->storeCasaPorCasaFile($request->file('xlsx_file'));
        $fullPath = $this->upload->fullPath($path);

        try {
            $periodo = $periodos->preparar(
                ServicioPeriodosImportacion::TIPO_CASA_X_CASA,
                $anio,
                $trimestre,
                $request->validated('fecha_corte'),
                basename($path),
                $reemplazar,
            );
        } catch (\Throwable $e) {
            return back()->withInput($request->except('xlsx_file'))->with('error', $e->getMessage());
        }

        $exitCode = Artisan::call('casa-x-casa:import', [
            'file' => $fullPath,
            '--periodo' => $periodo->id,
            '--no-interaction' => true,
        ]);

        $output = Artisan::output();

        if ($exitCode !== 0) {
            return back()->with('error', 'Error al importar: '.$output);
        }

        DashboardController::invalidateDashboardCache();

        $lines = explode("\n", trim($output));
        $summary = collect($lines)->filter(fn ($l) => str_starts_with($l, 'Importación'))->first() ?? 'OK';

        return back()->with('success', "Archivo CxC importado correctamente. {$summary}");
    }

    public function uploadForm()
    {
        return view('upload-form');
    }
}
