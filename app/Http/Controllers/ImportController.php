<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadCasaPorCasaRequest;
use App\Http\Requests\UploadImportRequest;
use App\Jobs\FinalizarImportacionJob;
use App\Jobs\ProcesarChunkCsvJob;
use App\Servicios\ServicioAlcanceUsuario;
use App\Servicios\ServicioMapeoColumnas;
use App\Servicios\ServicioPeriodosImportacion;
use App\Servicios\ServicioSanitizadorCsv;
use App\Servicios\ServicioUpload;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ImportController extends Controller
{
    public function __construct(
        private ServicioUpload $upload,
        private ServicioPeriodosImportacion $periodos,
        ServicioAlcanceUsuario $alcanceUsuario,
    ) {
        parent::__construct($alcanceUsuario);
    }

    public function index()
    {
        return view('imports', [
            'periodosActivos' => $this->periodos->activos(request()->user()),
            'trimestres' => $this->periodos->trimestres(),
            'currentYear' => (int) now()->year,
        ]);
    }

    public function upload(UploadImportRequest $request, ServicioSanitizadorCsv $sanitizer)
    {
        $anio = (int) $request->validated('anio');
        $trimestre = $request->validated('trimestre');
        $reemplazar = $request->boolean('reemplazar_periodo');

        if ($reemplazar && ! $request->user()->canImportGlobal()) {
            return back()->withInput($request->except('csv_file'))
                ->with('error', 'Solo usuarios con acceso nacional pueden reemplazar periodos.');
        }

        try {
            if ($this->periodos->existe(ServicioPeriodosImportacion::TIPO_REGULAR, $anio, $trimestre) && ! $reemplazar) {
                return back()
                    ->withInput($request->except('csv_file'))
                    ->with('error', $this->periodos->mensajeReemplazo(ServicioPeriodosImportacion::TIPO_REGULAR, $anio, $trimestre));
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

        $scope = $this->periodos->scopeFromUser($request->user());

        try {
            $periodo = $this->periodos->preparar(
                ServicioPeriodosImportacion::TIPO_REGULAR,
                $anio,
                $trimestre,
                $request->validated('fecha_corte'),
                $fileName,
                $reemplazar,
                scopeType: $scope['scopeType'],
                regionId: $scope['regionId'],
                unidadOperativaId: $scope['unidadOperativaId'],
                uploadedBy: $request->user()?->id,
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

        Bus::batch($jobs)
            ->name("Importación web: {$fileName}")
            ->allowFailures()
            ->onQueue('imports')
            ->then(function ($batch) use ($periodo) {
                if ($batch->hasFailures()) {
                    Log::warning('Importación cancelada por fallos en chunks', [
                        'periodo_id' => $periodo->id,
                        'failed_jobs' => $batch->failedJobs,
                    ]);

                    $this->periodos->marcarError((int) $periodo->id);

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

    public function uploadCasaPorCasa(UploadCasaPorCasaRequest $request)
    {
        $anio = (int) $request->validated('anio');
        $trimestre = $request->validated('trimestre');
        $reemplazar = $request->boolean('reemplazar_periodo');

        if ($reemplazar && ! $request->user()->canImportGlobal()) {
            return back()->withInput($request->except('xlsx_file'))
                ->with('error', 'Solo usuarios con acceso nacional pueden reemplazar periodos.');
        }

        if ($this->periodos->existe(ServicioPeriodosImportacion::TIPO_CASA_X_CASA, $anio, $trimestre) && ! $reemplazar) {
            return back()
                ->withInput($request->except('xlsx_file'))
                ->with('error', $this->periodos->mensajeReemplazo(ServicioPeriodosImportacion::TIPO_CASA_X_CASA, $anio, $trimestre));
        }

        $path = $this->upload->storeCasaPorCasaFile($request->file('xlsx_file'));
        $fullPath = $this->upload->fullPath($path);

        try {
            $periodo = $this->periodos->preparar(
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

        Cache::flush();

        $summary = $this->extractSummaryFromOutput($output);

        return back()->with('success', "Archivo CxC importado correctamente. {$summary}");
    }

    private function extractSummaryFromOutput(string $output): string
    {
        $lines = explode("\n", trim($output));

        return collect($lines)->filter(fn ($l) => str_starts_with($l, 'Importación'))->first() ?? 'OK';
    }
}
